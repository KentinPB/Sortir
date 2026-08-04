<?php

namespace App\Controller\Admin;

use App\Entity\Ville;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/city', name: 'app_admin_city_')]
final class CityController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(VilleRepository $villeRepository): Response
    {
        return $this->render('admin/city.html.twig', [
            'cities' => $villeRepository->findAll(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Logique de création de la ville

        return $this->redirectToRoute('app_admin_city_list');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Ville $ville, Request $request, EntityManagerInterface $em): Response
    {
        // TODO: Logique d'édition de la ville

        return $this->render('admin/city_edit.html.twig', [
            'city' => $ville,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Ville $ville, EntityManagerInterface $em): Response
    {
        // TODO: Logique de suppression de la ville

        return $this->redirectToRoute('app_admin_city_list');
    }
}
