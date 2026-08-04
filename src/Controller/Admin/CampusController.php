<?php

namespace App\Controller\Admin;

use App\Entity\Campus;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/campus', name: 'app_admin_campus_')]
final class CampusController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(CampusRepository $campusRepository): Response
    {
        return $this->render('admin/campus.html.twig', [
            'campuses' => $campusRepository->findAll(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Logique de création du campus (ex: récupération via $request->request->get('nameCampus'))

        return $this->redirectToRoute('app_admin_campus_list');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Campus $campus, Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Logique d'édition du campus

        return $this->render('admin/campus_edit.html.twig', [
            'campus' => $campus,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Campus $campus, EntityManagerInterface $em): Response
    {
        // TODO: Logique de suppression du campus

        return $this->redirectToRoute('app_admin_campus_list');
    }
}
