<?php

namespace App\Controller\Admin;

use App\Entity\Ville;
use App\Form\VilleType;
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
    #[Route('', name: 'list', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function index(
        ?Ville                 $ville,
        Request                $request,
        VilleRepository        $villeRepository,
        EntityManagerInterface $em
    ): Response
    {
        $isEdit = $ville !== null;
        $ville = $ville ?? new Ville();

        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isEdit) {
                $em->persist($ville);
            }
            $em->flush();

            $this->addFlash('success', $isEdit ? 'La ville a été modifiée.' : 'La ville a été ajoutée.');

            return $this->redirectToRoute('app_admin_city_list', [], Response::HTTP_SEE_OTHER);
        }

        $status = ($form->isSubmitted() && !$form->isValid())
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('admin/city.html.twig', [
            'villes' => $villeRepository->findAll(),
            'form' => $form->createView(),
            'isEdit' => $isEdit,
            'ville' => $ville,
        ], new Response(null, $status));
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Ville                  $ville,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ville->getId(), $request->request->get('_token'))) {
            $em->remove($ville);
            $em->flush();
            $this->addFlash('success', 'La ville a été supprimée.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_admin_city_list', [], Response::HTTP_SEE_OTHER);
    }
}
