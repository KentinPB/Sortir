<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Service\SortieStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 *  PHP Doc
 */
class MainController extends AbstractController
{

    #[Route('/', name: 'accueil')]
    public function index(
        Request            $request,
        SortieRepository   $sortieRepository,
        CampusRepository   $campusRepository,
        SortieStateManager $stateManager
    ): Response
    {
        $user = $this->getUser();

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

        // <-- Mise à jour automatique des états en fonction de l'horodatage actuel
        $stateManager->updateEtats($sorties);

        $campusList = $campusRepository->findAll();

        return $this->render('main/index.html.twig', [
            'sorties' => $sorties,
            'campusList' => $campusList,
        ]);
    }

    #[Route('/sortie/{id}/inscrire', name: 'sortie_inscrire')]
    #[IsGranted('ROLE_USER')]
    public function inscrire(
        Sortie                 $sortie,
        EntityManagerInterface $em,
        SortieStateManager     $stateManager
    ): Response
    {
        // Réévaluation rapide de l'état avant traitement
        $stateManager->updateEtat($sortie);

        if ($sortie->getEtat()->getLibelle() !== Etat::OUVERTE) {
            $this->addFlash('danger', "Cette sortie n'est pas ouverte aux inscriptions.");
            return $this->redirectToRoute('accueil');
        }

        $user = $this->getUser();
        if (!$sortie->getParticipants()->contains($user)) {
            $sortie->addParticipant($user);

            // Re-vérifier si la sortie est complète après l'ajout
            $stateManager->updateEtat($sortie);

            $em->flush();
            $this->addFlash('success', 'Inscription réussie !');
        }
        return $this->redirectToRoute('accueil');
    }

    #[Route('/sortie/{id}/desister', name: 'sortie_desister')]
    #[IsGranted('ROLE_USER')]
    public function desister(
        Sortie $sortie,
        EntityManagerInterface $em,
        SortieStateManager $stateManager
    ): Response {
        // Vérification de la date de début
        if ($sortie->getDateHeureDebut() <= new \DateTime()) {
            $this->addFlash('danger', "Impossible de se désister, la sortie a déjà débuté.");
            return $this->redirectToRoute('accueil');
        }

        // Autoriser le désistement uniquement si la sortie est Ouverte ou Clôturée
        $etatLibelle = $sortie->getEtat()?->getLibelle();
        if (!in_array($etatLibelle, [Etat::OUVERTE, Etat::CLOTUREE], true)) {
            $this->addFlash('danger', "Vous ne pouvez pas vous désister de cette sortie.");
            return $this->redirectToRoute('accueil');
        }

        $user = $this->getUser();
        if ($sortie->getParticipants()->contains($user)) {
            $sortie->removeParticipant($user);

            // Recalculer l'état (ex : si la sortie était clôturée, car complète, elle repasse Ouverte)
            $stateManager->updateEtat($sortie);

            $em->flush();
            $this->addFlash('info', "Tu t'es désisté de la sortie.");
        }

        return $this->redirectToRoute('accueil');
    }

}
