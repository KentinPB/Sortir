<?php

namespace App\Service;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;

class SortieStateManager
{
    private array $etatCache = [];

    public function __construct(
        private readonly EtatRepository         $etatRepository,
        private readonly EntityManagerInterface $em
    )
    {
    }

    private function getEtat(string $libelle): ?Etat
    {
        if (!isset($this->etatCache[$libelle])) {
            $this->etatCache[$libelle] = $this->etatRepository->findOneBy([
                'libelle' => $libelle
            ]);
        }

        return $this->etatCache[$libelle];
    }
    /**
     * Met à jour automatiquement l'état d'une sortie.
     *
     * @return bool true si l'état a changé.
     */
    public function updateEtat(Sortie $sortie): bool
    {
        $currentEtat = $sortie->getEtat()?->getLibelle();

        //dump([$sortie->getNom(), $currentEtat]);

        // Les sorties en création ou historisées ne sont jamais modifiées automatiquement.
        if (in_array($currentEtat, [Etat::CREEE, Etat::HISTORISEE], true)) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $dateDebut = \DateTimeImmutable::createFromMutable($sortie->getDateHeureDebut());
        $dateFin = $dateDebut->modify(sprintf('+%d minutes', $sortie->getDuree()));
        $dateHistorisation = $dateFin->modify('+1 month');
        $dateLimite = \DateTimeImmutable::createFromMutable($sortie->getDateLimiteInscription());
        $dateLimite = $dateLimite->setTime(23, 59, 59);

        /*dump([
            'Nom de la sortie' => $sortie->getNom(),
            'DateNow' => $now,
            'dateLimite' => $dateLimite,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'dateHistorisation' => $dateHistorisation,
            'currentEtat' => $currentEtat,
        ]);*/

        $nbParticipants = $sortie->getParticipants()->count();
        $nbMax = $sortie->getNbInscriptionsMax();

        $targetEtatLibelle = null;

        // Cas particulier : Sortie annulée
        if ($currentEtat === Etat::ANNULEE) {
            if ($this->shouldBeHistorisee($now, $dateHistorisation)) {
                $targetEtatLibelle = Etat::HISTORISEE;
            }
        } else {
            // Évaluation séquentielle par ordre de priorité décroissante
            if ($this->shouldBeHistorisee($now, $dateHistorisation)) {
                $targetEtatLibelle = Etat::HISTORISEE;
            } elseif ($this->shouldBeTerminee($now, $dateFin)) {
                $targetEtatLibelle = Etat::TERMINEE;
            } elseif ($this->shouldBeEnCours($now, $dateDebut, $dateFin)) {
                $targetEtatLibelle = Etat::EN_COURS;
            } elseif ($this->shouldBeCloturee($now, $dateLimite, $nbParticipants, $nbMax)) {
                $targetEtatLibelle = Etat::CLOTUREE;
            } else {
                $targetEtatLibelle = Etat::OUVERTE;
            }
        }

        if (
            $targetEtatLibelle !== null &&
            $targetEtatLibelle !== $currentEtat
        ) {
            $etat = $this->getEtat($targetEtatLibelle);

            if ($etat !== null) {
                $sortie->setEtat($etat);
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si la sortie doit être historisée.
     */
    private function shouldBeHistorisee(
        \DateTimeImmutable $now,
        \DateTimeImmutable $dateHistorisation
    ): bool
    {
        return $now >= $dateHistorisation;
    }

    /**
     * Vérifie si la sortie est terminée.
     */
    private function shouldBeTerminee(\DateTimeImmutable $now, \DateTimeImmutable $dateFin): bool
    {
        return $now >= $dateFin;
    }

    /**
     * Vérifie si l'activité est en cours.
     */
    private function shouldBeEnCours(
        \DateTimeImmutable $now,
        \DateTimeImmutable $dateDebut,
        \DateTimeImmutable $dateFin
    ): bool
    {
        return $now >= $dateDebut && $now < $dateFin;
    }

    /**
     * Vérifie si la sortie doit être clôturée (date limite dépassée ou nombre max atteint).
     */
    private function shouldBeCloturee(
        \DateTimeImmutable $now,
        \DateTimeImmutable $dateLimite,
        int                $nbParticipants,
        int                $nbMax
    ): bool
    {
        return $now > $dateLimite
            || $nbParticipants >= $nbMax;
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
