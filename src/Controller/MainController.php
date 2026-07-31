<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use App\Repository\CampusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EtatRepository;


/**
 *  PHP Doc
 */
class MainController extends AbstractController
{


    #[Route('/', name: 'accueil')]
    //#[IsGranted('ROLE_USER')]
    public function index(Request $request, SortieRepository $sortieRepository, CampusRepository $campusRepository): Response
    {
        $user = $this->getUser();

        // Le campus est proposé automatiquement selon celui de l'utilisateur,
        // sauf si l'utilisateur choisit explicitement un autre campus dans le filtre
        $campus = $request->query->get('campus') ?: $user?->getCampus()?->getId();

        $nom = $request->query->get('nom');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $estOrganisateur = $request->query->getBoolean('estOrganisateur');
        $estInscrit = $request->query->getBoolean('estInscrit');
        $estNonInscrit = $request->query->getBoolean('estNonInscrit');
        $sortiesTerminees = $request->query->getBoolean('sortiesTerminees');

        $sorties = $sortieRepository->findByFiltres(
            $campus, $nom, $dateDebut, $dateFin, $user,
            $estOrganisateur, $estInscrit, $estNonInscrit, $sortiesTerminees
        );
        $campusList = $campusRepository->findAll();
        return $this->render('sortir/index.html.twig', [
            'sorties' => $sorties,
            'campusList' => $campusList,
        ]);
    }

    #[Route('/sortie/{id}/inscrire', name: 'sortie_inscrire')]
    #[IsGranted('ROLE_USER')]
    public function inscrire(Sortie $sortie, EntityManagerInterface $em): Response
    {
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('danger', "Cette sortie n'est pas ouverte aux inscriptions.");
            return $this->redirectToRoute('accueil');
        }

        if ($sortie->getDateLimiteInscription() < new \DateTime()) {
            $this->addFlash('danger', "La date limite d'inscription est dépassée.");
            return $this->redirectToRoute('accueil');
        }

        if ($sortie->getParticipants()->count() >= $sortie->getNbInscriptionsMax()) {
            $this->addFlash('danger', "Le nombre maximum d'inscriptions est atteint.");
            return $this->redirectToRoute('accueil');
        }

        $user = $this->getUser();
        if (!$sortie->getParticipants()->contains($user)) {
            $sortie->addParticipant($user);
            $em->flush();
            $this->addFlash('success', 'Inscription réussie !');
        }
        return $this->redirectToRoute('accueil');
    }

    #[Route('/sortie/{id}/desister', name: 'sortie_desister')]
    #[IsGranted('ROLE_USER')]
    public function desister(Sortie $sortie, EntityManagerInterface $em): Response
    {
        if ($sortie->getDateHeureDebut() <= new \DateTime()) {
            $this->addFlash('danger', "Impossible de se désister, la sortie a déjà débuté.");
            return $this->redirectToRoute('accueil');
        }

        $user = $this->getUser();
        if ($sortie->getParticipants()->contains($user)) {
            $sortie->removeParticipant($user);
            $em->flush();
            $this->addFlash('info', "Tu t'es désisté de la sortie.");
        }
        return $this->redirectToRoute('accueil');
    }

#[Route('/sortie/{id}', name: 'sortie_afficher', requirements: ['id' => '\d+'])]

public function afficher(Sortie $sortie): Response
{
    return $this->render('sortir/detail.html.twig', ['sortie' => $sortie]);
}


#[Route('/sortie/{id}/annuler', name: 'sortie_annuler')]
#[IsGranted('ROLE_USER')]
public function annuler(Sortie $sortie, Request $request, EntityManagerInterface $em, EtatRepository $etatRepository): Response
{
    if ($sortie->getOrganisateur() !== $this->getUser()) {
        $this->addFlash('danger', "Seul l'organisateur peut annuler cette sortie.");
        return $this->redirectToRoute('accueil');
    }

    if ($sortie->getEtat()->getLibelle() !== 'Ouverte' || $sortie->getDateHeureDebut() <= new \DateTime()) {
        $this->addFlash('danger', 'Cette sortie ne peut pas être annulée.');
        return $this->redirectToRoute('accueil');
    }

    if ($request->isMethod('POST')) {
        $motif = $request->request->get('motif');
        $sortie->setMotifAnnulation($motif);
        $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Annulée']));
        $em->flush();
        $this->addFlash('success', 'La sortie a été annulée.');
        return $this->redirectToRoute('accueil');
    }

    return $this->render('sortir/annuler.html.twig', ['sortie' => $sortie]);
}
}
