<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'app_sortie_')]
class SortieController extends AbstractController
{
    #[Route('/creer', name: 'creer', methods: ['GET', 'POST'])]
    public function creer(
        Request                $request,
        EntityManagerInterface $entityManager,
        ParticipantRepository  $participantRepository,
        EtatRepository         $etatRepository
    ): Response
    {
        // -------------------------------------------------------------
        // 1. MOCK DE L'UTILISATEUR (À remplacer plus tard par $this->getUser())
        // On récupère arbitrairement le participant "Jeannine L." créé dans les fixtures
        // -------------------------------------------------------------
        $userMock = $participantRepository->findOneBy(['pseudo' => 'Jeannine L.']);

        if (!$userMock) {
            throw $this->createNotFoundException('Utilisateur Mock introuvable. Avez-vous lancé les fixtures ?');
        }

        // 2. Initialisation de la nouvelle Sortie
        $sortie = new Sortie();

        // On associe automatiquement l'organisateur (notre mock) et son campus
        $sortie->setOrganisateur($userMock);

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

            // Redirection après modification (vers l'accueil ou le détail de la sortie)
            // TODO: Mettre la route vers la liste des sorties (ex: path('app_main_home'))
            return $this->redirectToRoute('app_sortie_creer');
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

    #[Route('/modifier/{id}', name: 'modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(
        int                    $id,
        Request                $request,
        EntityManagerInterface $entityManager,
        SortieRepository       $sortieRepository,
        EtatRepository         $etatRepository
    ): Response
    {
        // 1. Récupérer la sortie existante en base de données
        $sortie = $sortieRepository->findOneForEdit($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Cette sortie n\'existe pas.');
        }

        // 2. Création et gestion du formulaire avec l'instance existante
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

            // Redirection après modification (vers l'accueil ou le détail de la sortie)
            // TODO: Mettre la route vers la liste des sorties (ex: path('app_main_home'))
            return $this->redirectToRoute('app_sortie_creer');
        }

        // 4. Affichage de la vue (vous pouvez réutiliser le même template que la création)
        return $this->render('sortie/form.html.twig', [
            'sortieForm' => $form->createView(),
            'isEdit' => true, // Utile pour adapter le titre de la page dans Twig si besoin
            'sortie' => $sortie,
        ]);
    }

    #[Route('/supprimer/{id}', name: 'supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        // 1. Vérification des droits (Organisateur de la sortie ou Administrateur)
        /*
        if (!($sortie->getOrganisateur() === $this->getUser() || $this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException('Vous devez être l\'organisateur ou Administrateur pour supprimer cette sortie !');
        }
        */

        // 2. Validation du token CSRF (provenant d'un formulaire POST)
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Action non autorisée (jeton invalide).');
        }

        // 3. Redirection vers la liste des sorties
        // TODO: Mettre la route vers la liste des sorties (ex: path('app_main_home'))
        return $this->redirectToRoute('app_sortie_creer');
    }

    #[Route('/annuler/{id}', name: 'annuler', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function annuler(
        int                    $id,
        Request                $request,
        EntityManagerInterface $entityManager,
        EtatRepository         $etatRepository,
        SortieRepository       $sortieRepository
    ): Response
    {
        $sortie = $sortieRepository->findOneForCancel($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie introuvable.');
        }

        $libelleEtat = $sortie->getEtat()?->getLibelle();

        if (!in_array($libelleEtat, ['Ouverte', 'Clôturée']) || $sortie->getDateHeureDebut() <= new \DateTime()) {
            $this->addFlash('danger', 'Cette sortie ne peut pas être annulée.');

            return $this->redirectToRoute('app_sortie_creer');
        }

        if ($request->isMethod('POST')) {
            $motif = trim((string)$request->request->get('motif'));

            if (empty($motif)) {
                $this->addFlash('danger', 'Le motif d\'annulation est obligatoire.');
            } else {
                $etatAnnulee = $etatRepository->findOneBy([
                    'libelle' => 'Annulée'
                ]);

                if (!$etatAnnulee) {
                    $this->addFlash('danger', 'L\'état "Annulée" est introuvable en base de données.');

                    return $this->redirectToRoute('app_sortie_creer');
                }

                $sortie->setMotifAnnulation($motif);
                $sortie->setEtat($etatAnnulee);

                $entityManager->flush();

                $this->addFlash('success', 'La sortie a bien été annulée.');

                return $this->redirectToRoute('app_sortie_creer');
            }
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortie' => $sortie,
        ]);
    }
}
