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
    ) {
    }

    /**
     * Met à jour automatiquement l'état d'une sortie.
     *
     * @return bool true si l'état a changé.
     */
    public function updateEtat(Sortie $sortie): bool
    {
        $currentEtat = $sortie->getEtat()?->getLibelle();

        // Les sorties en création ou historisées ne sont jamais modifiées automatiquement.
        if (in_array($currentEtat, [Etat::CREEE, Etat::HISTORISEE], true)) {
            return false;
        }

        $now = new \DateTimeImmutable();

        $dateDebut = \DateTimeImmutable::createFromMutable($sortie->getDateHeureDebut());

        $dateFin = $dateDebut->modify(sprintf('+%d minutes', $sortie->getDuree()));

        $dateHistorisation = $dateFin->modify('+1 month');

        $dateLimite = \DateTimeImmutable::createFromMutable(
            $sortie->getDateLimiteInscription()
        );

        $nbParticipants = $sortie->getParticipants()->count();

        $nbMax = $sortie->getNbInscriptionsMax();

        $targetEtatLibelle = null;

        /**
         * ===========================
         * Cas particulier : ANNULÉE
         * ===========================
         */
        if ($currentEtat === Etat::ANNULEE) {

            if ($now >= $dateHistorisation) {
                $targetEtatLibelle = Etat::HISTORISEE;
            }

        } else {

            /**
             * ===========================
             * 1. Historisée (1 mois après la fin)
             * ===========================
             */
            if ($now >= $dateHistorisation) {

                $targetEtatLibelle = Etat::HISTORISEE;

            }
            /**
             * ===========================
             * 2. Terminée (La sortie est passée)
             * ===========================
             */
            elseif ($now >= $dateFin) {

                $targetEtatLibelle = Etat::TERMINEE;

            }
            /**
             * ===========================
             * 3. Activité en cours (Elle a commencé mais pas finie)
             * ===========================
             */
            elseif ($now >= $dateDebut) {

                $targetEtatLibelle = Etat::EN_COURS;

            }
            /**
             * ===========================
             * 4. Clôturée (Date limite dépassée OU complet)
             * ===========================
             */
            elseif (
                $now > $dateLimite ||
                $nbParticipants >= $nbMax
            ) {

                $targetEtatLibelle = Etat::CLOTUREE;

            }
            /**
             * ===========================
             * 5. Ouverte (Par défaut)
             * ===========================
             */
            else {

                $targetEtatLibelle = Etat::OUVERTE;

            }
        }

        if (
            $targetEtatLibelle !== null &&
            $targetEtatLibelle !== $currentEtat
        ) {

            $etat = $this->etatRepository->findOneBy([
                'libelle' => $targetEtatLibelle
            ]);

            if ($etat !== null) {
                $sortie->setEtat($etat);
                return true;
            }
        }

        return false;
    }

    /**
     * Met à jour les états d'une collection de sorties.
     *
     * @param iterable<Sortie> $sorties
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
