<?php

namespace App\Service;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;

class SortieStateManager
{
    public function __construct(
        private readonly EtatRepository $etatRepository,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Calcule et met à jour l'état d'une sortie spécifique.
     */
    public function updateEtat(Sortie $sortie): bool
    {
        $currentEtat = $sortie->getEtat()?->getLibelle();

        // Ne pas toucher aux sorties en création ou annulées
        if (in_array($currentEtat, [Etat::CREEE, Etat::ANNULEE, Etat::HISTORISEE], true)) {
            return false;
        }

        $now = new \DateTime();
        $dateDebut = $sortie->getDateHeureDebut();

        // Calcul de la date de fin = dateDebut + durée (convertie en minutes)
        $dateFin = (clone $dateDebut)->modify('+' . $sortie->getDuree() . ' minutes');

        // Date d'archivage/historisation (1 mois après la fin de la sortie)
        $dateHistorisation = (clone $dateFin)->modify('+1 month');

        $targetEtatLibelle = null;

        if ($now >= $dateHistorisation) {
            $targetEtatLibelle = Etat::HISTORISEE;
        } elseif ($now >= $dateFin) {
            $targetEtatLibelle = Etat::PASSEE;
        } elseif ($now >= $dateDebut && $now < $dateFin) {
            $targetEtatLibelle = Etat::EN_COURS;
        } elseif ($now >= $sortie->getDateLimiteInscription() || $sortie->getParticipants()->count() >= $sortie->getNbInscriptionsMax()) {
            $targetEtatLibelle = Etat::CLOTUREE;
        } else {
            // Si la date limite n'est pas dépassée et qu'il reste de la place
            $targetEtatLibelle = Etat::OUVERTE;
        }

        // Si l'état a changé, on applique la modification
        if ($targetEtatLibelle && $currentEtat !== $targetEtatLibelle) {
            $etatEntity = $this->etatRepository->findOneBy(['libelle' => $targetEtatLibelle]);
            if ($etatEntity) {
                $sortie->setEtat($etatEntity);
                return true;
            }
        }

        return false;
    }

    /**
     * Met à jour les états pour une collection de sorties et applique un flush s'il y a eu des modifications.
     *
     * @param Sortie[] $sorties
     */
    public function updateEtats(iterable $sorties): void
    {
        $hasChanges = false;
        foreach ($sorties as $sortie) {
            if ($this->updateEtat($sortie)) {
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $this->em->flush();
        }
    }
}
