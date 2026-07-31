<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\AnnulationSortieType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
#[Route('/sortie', name: 'app_sortie_')]
class SortieController extends AbstractController
{

    public function __construct(
        private readonly ParticipantRepository $participantRepository
    )
    {
    }

    private function getConnectedUser(): Participant
    {
        return $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException(
                'Utilisateur mock introuvable.'
            );
        }

        return $user;
    }

    #[Route('/creer', name: 'creer', methods: ['GET', 'POST'])]
    public function creer(
        Request                $request,
        EntityManagerInterface $entityManager,
        ParticipantRepository  $participantRepository,
        EtatRepository         $etatRepository
    ): Response
    {
        // 1. Récupération de l'utilisateur connecté
        $userConnected = $this->getConnectedUser();

        // 2. Initialisation de la nouvelle Sortie
        $sortie = new Sortie();

        // On associe automatiquement l'organisateur (notre mock) et son campus
        $sortie->setOrganisateur($userConnected);

        // 3. Création et gestion du formulaire
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 4. Gestion de la double action (Enregistrer vs Publier)
            // On vérifie quel bouton a été cliqué dans le formulaire
            if ($form->get('enregistrer')->isClicked()) {
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
                $this->addFlash('success', 'La sortie a été enregistrée en brouillon.');
            } elseif ($form->get('publier')->isClicked()) {
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                $this->addFlash('success', 'La sortie a été publiée avec succès !');
            } else {
                // Sécurité : état par défaut si problème
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
            }

            $sortie->setEtat($etat);

            // 5. Sauvegarde en base de données
            $entityManager->persist($sortie);
            $entityManager->flush();

            // Redirection après modification (vers le détail de la sortie)
            return $this->redirectToRoute('sortie_afficher', ['id' => $sortie->getId()]);
        }

        // 6. Affichage de la vue
        return $this->render('sortie/form.html.twig', [
            'sortieForm' => $form,
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid()
                ? Response::HTTP_UNPROCESSABLE_ENTITY // Code 422 pour Turbo
                : Response::HTTP_OK                   // Code 200 normal
        ));
    }

// Contrainte modification d'une sortie si non publiée (etat = "En création") par l'organisateur (avec vérification des droits)
    #[Route('/modifier/{id}', name: 'modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(
        int                    $id,
        Request                $request,
        EntityManagerInterface $entityManager,
        SortieRepository       $sortieRepository,
        EtatRepository         $etatRepository,
        ParticipantRepository  $participantRepository
    ): Response
    {
        $userConnected = $this->getConnectedUser();

        // 1. Récupérer la sortie existante en base de données
        $sortie = $sortieRepository->findOneForEdit($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Cette sortie n\'existe pas.');
        }

        // 2. Vérification des droits et du statut
        if (!$sortie->isOrganisateur($userConnected)) {
            $this->addFlash('danger', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
            return $this->redirectToRoute('accueil');
        }

        if (!$sortie->isCreee()) {
            $this->addFlash('danger', 'Cette sortie n\'est plus en création et ne peut plus être modifiée.');
            return $this->redirectToRoute('accueil');
        }

        // 3. Création et gestion du formulaire avec l'instance existante
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion optionnelle des boutons (si vous avez gardé Enregistrer / Publier)
            if ($form->has('publier') && $form->get('publier')->isClicked()) {
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                if ($etat) {
                    $sortie->setEtat($etat);
                }
                $this->addFlash('success', 'La sortie a été modifiée et publiée avec succès !');
            } elseif ($form->has('enregistrer') && $form->get('enregistrer')->isClicked()) {
                $this->addFlash('success', 'Les modifications de la sortie ont été enregistrées.');
            }

            // 3. Mise à jour en base de données
            // Pas de persist() nécessaire, car l'entité existe déjà (gérée par Doctrine)
            $entityManager->flush();
            return $this->redirectToRoute('sortie_afficher', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/form.html.twig', [
            'sortieForm' => $form->createView(),
            'isEdit' => true,
            'sortie' => $sortie,
            'userConnected' => $userConnected, // <--- Passe ton mock user à la vue
        ]);
    }

// Contrainte suppression d'une sortie si non publiée (etat = "En création") par l'organisateur (avec vérification des droits)
    #[Route('/supprimer/{id}', name: 'supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $userConnected = $this->getConnectedUser();

        // 1. Vérification des droits et du statut "En création"
        if (!$sortie->isOrganisateur($userConnected)) {
            $this->addFlash('danger', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
            return $this->redirectToRoute('accueil');
        }

        if (!$sortie->isCreee()) {
            $this->addFlash('danger', 'Seules les sorties en création peuvent être supprimées.');
            return $this->redirectToRoute('accueil');
        }

        // 2. Vérification du token CSRF pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été supprimée définitivement.');
        } else {
            $this->addFlash('danger', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('accueil');
    }

// Contrainte annulation d'une sortie si publiée (etat = "Ouverte" ou "Clôturée") par l'organisateur (avec vérification des droits)
    #[Route('/annuler/{id}', name: 'annuler', methods: ['GET', 'POST'])]
    public function annuler(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $entityManager,
        EtatRepository         $etatRepository,
    ): Response
    {
        $userConnected = $this->getConnectedUser();

        if (!$userConnected) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // 1. Vérification des droits, de la date et de l'état
        if (!$sortie->isOrganisateur($userConnected)) {
            $this->addFlash('danger', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
            return $this->redirectToRoute('accueil');
        }

        if (!$sortie->isPubliee()) {
            $this->addFlash('danger', 'La sortie doit être ouverte ou clôturée pour être annulée.');
            return $this->redirectToRoute('accueil');
        }

        if (!$sortie->isNonCommencee()) {
            $this->addFlash('danger', 'La sortie a déjà commencé.');
            return $this->redirectToRoute('accueil');
        }

        // 2. Création du formulaire d'annulation
        $form = $this->createForm(AnnulationSortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etatAnnulee = $etatRepository->findOneBy(['libelle' => Etat::ANNULEE]);

            if (!$etatAnnulee) {
                $this->addFlash('danger', 'L\'état "Annulée" est introuvable en base de données.');
                return $this->redirectToRoute('accueil');
            }

            $sortie->setEtat($etatAnnulee);
            $entityManager->flush();
            $this->addFlash('success', 'La sortie a bien été annulée.');
            return $this->redirectToRoute('sortie_afficher', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }
}
