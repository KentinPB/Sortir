<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Participant;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de modification du profil de l'utilisateur connecté.
     *
     * Permet de mettre à jour les informations personnelles, le mot de passe
     * et la photo de profil.
     */
    #[Route('/profil', name: 'app_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response
    {
        /** @var Participant $participant */
        $participant = $this->getUser();

        $form = $this->createForm(ProfileType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        // -----------------------------
        // Mise à jour du mot de passe
        // -----------------------------

            // Met à jour le mot de passe uniquement si un nouveau mot de passe est saisi.
            $newPassword = $form->get('plainPassword')->getData();

            if (!empty($newPassword)) {
                $participant->setPassword(
                    $passwordHasher->hashPassword($participant, $newPassword)
                );
            }
        // -----------------------------
        // Gestion de la photo de profil
        // -----------------------------

            // Traite l'upload d'une nouvelle photo de profil.
             $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {

                // Récupère le nom du fichier d'origine, sans son extension.
                $originalFilename = pathinfo(
                    $photoFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                // Génère un nom de fichier compatible avec une URL en supprimant les caractères spéciaux.
                $safeFilename = $slugger->slug($originalFilename);
                // Construit un nom de fichier unique afin d'éviter les collisions.
                // L'extension est déterminée à partir du type réel du fichier et non du nom fourni par l'utilisateur.
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                // Déplace le fichier du répertoire temporaire vers le dossier de stockage de l'application.
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $participant->setPhoto($newFilename);
                } catch (FileException $e) {
                    // En cas d'échec, ne pas enregistrer de référence vers un fichier inexistant.
                    $this->addFlash(
                        'error',
                        'Une erreur est survenue lors de l\'import de la photo.'
                    );
                }
                // TODO : supprimer l'ancienne photo après un remplacement (unlink()).

            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre profil a été mis à jour avec succès !'
            );
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/profileForm.html.twig', [

            'participant' => $participant,
            'form' => $form->createView(),
        ], new Response(null,  $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * Affiche le profil public d'un participant.
     */
    #[Route('/profil/{id}', name: 'app_profile_show')]
    public function show(Participant $participant): Response
    {
        return $this->render('profile/show.html.twig', [
            'participant' => $participant,
        ]);
    }
}
