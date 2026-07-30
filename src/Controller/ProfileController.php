<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ChangePasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher): Response
    {
        $participant = $this->getUser();
        $form = $this->createForm(ProfileType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();

            if (!empty($newPassword)) {
                $participant->setPassword(
                    $passwordHasher->hashPassword($participant, $newPassword)
                );
            }
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre profil a été mis à jour avec succès !'
            );
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [

            'participant' => $participant,
            'form' => $form->createView(),
        ], new Response(null,  $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
