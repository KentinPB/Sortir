<?php

namespace App\Controller\Admin;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Logique d'ajout d'un utilisateur

        return $this->render('admin/user_add.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Participant $participant, Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Logique d'édition de l'utilisateur

        return $this->render('admin/user_edit.html.twig', [
            'user' => $participant,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Participant $participant, EntityManagerInterface $em): Response
    {
        // TODO: Logique de suppression / désactivation de l'utilisateur

        return $this->redirectToRoute('app_admin_users_list');
    }
}
