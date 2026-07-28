<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sortie', name: 'app_sortie_')]
class SortieController extends AbstractController
{
    #[Route('/creer', name: 'creer')]
    public function creer(
        Request $request,
        EntityManagerInterface $entityManager,
        ParticipantRepository $participantRepository,
        EtatRepository $etatRepository
    ): Response {
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
        $sortie->setSiteOrganisateur($userMock->getCampus());

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

            // Redirection vers l'accueil ou la page de détail de la sortie
            return $this->redirectToRoute('app_home'); // Remplacer par la route souhaitée
        }

        // 6. Affichage de la vue
        return $this->render('sortie/creer.html.twig', [
            'sortieForm' => $form->createView(),
        ]);
    }
}
