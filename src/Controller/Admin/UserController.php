<?php

namespace App\Controller\Admin;

use App\Entity\Participant;
use App\Form\ProfileType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users', name: 'app_admin_users_')]
final class UserController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(ParticipantRepository $participantRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $participantRepository->findAll(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $participant = new Participant();
        $participant->setActif(true); // Actif par défaut

        $form = $this->createForm(ProfileType::class, $participant, [
            'is_admin' => true,
            'is_new' => true, // Oblige la saisie du mot de passe
        ]);

        $form->handleRequest($request); // <-- Ne pas oublier la gestion de la requête !

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }

            $em->persist($participant);
            $em->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');

            return $this->redirectToRoute('app_admin_users_list');
        }

        return $this->render('profile/profileForm.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant, // <-- Passer la variable participant au template
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Participant            $participant,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        $isSelf = ($participant === $this->getUser());

        $form = $this->createForm(ProfileType::class, $participant, [
            'is_admin' => true,
            'is_self' => $isSelf,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sécurité serveur : empêche toute modification via l'inspecteur HTML
            if ($isSelf) {
                $participant->setActif(true);
                $participant->setAdministrateur(true);
            }

            $em->flush();
            $this->addFlash('success', 'Le profil de l\'utilisateur a été mis à jour.');

            return $this->redirectToRoute('app_admin_users_list');
        }

        return $this->render('profile/profileForm.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'toggle_active', methods: ['POST'])]
    public function toggleActive(
        Participant            $participant,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        // Sécurité CSRF
        if (!$this->isCsrfTokenValid('toggle_active_' . $participant->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_users_list');
        }

        // Empêcher l'administrateur connecté de se désactiver lui-même
        if ($participant === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
            return $this->redirectToRoute('app_admin_users_list');
        }

        // Inversion du statut
        $participant->setActif(!$participant->isActif());
        $em->flush();

        $statusMessage = $participant->isActif() ? 'activé' : 'désactivé';
        $this->addFlash('success', sprintf('Le compte de %s a été %s avec succès.', $participant->getPseudo(), $statusMessage));

        return $this->redirectToRoute('app_admin_users_list');
    }
}
