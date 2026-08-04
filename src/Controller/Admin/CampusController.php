<?php

namespace App\Controller\Admin;

use App\Entity\Campus;
use App\Form\CampusType;
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
    /**
     * Gère à la fois l'affichage de la liste, l'ajout et l'édition
     */
    #[Route('', name: 'list', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function index(
        ?Campus                $campus,
        Request                $request,
        CampusRepository       $campusRepository,
        EntityManagerInterface $em
    ): Response
    {
        // Si aucun campus n'est passé en paramètre, on crée une nouvelle instance (mode Ajout)
        $isEdit = $campus !== null;
        $campus = $campus ?? new Campus();

        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isEdit) {
                $em->persist($campus);
            }
            $em->flush();

            $message = $isEdit ? 'Le campus a été modifié.' : 'Le campus a été ajouté.';
            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_admin_campus_list', [], Response::HTTP_SEE_OTHER);
        }

        $status = ($form->isSubmitted() && !$form->isValid())
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('admin/campus.html.twig', [
            'campuses' => $campusRepository->findAll(),
            'form' => $form->createView(),
            'isEdit' => $isEdit,
            'campus' => $campus,
        ], new Response(null, $status));
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Campus $campus, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $campus->getId(), $request->request->get('_token'))) {
            $em->remove($campus);
            $em->flush();
            $this->addFlash('success', 'Le campus a été supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_campus_list', [], Response::HTTP_SEE_OTHER);
    }
}
