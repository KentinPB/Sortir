<?php

namespace App\Tests\Service;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\EtatRepository;
use App\Service\SortieStateManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Tests unitaires du calcul automatique de l'état d'une sortie.
 *
 * L'horloge est figée au 15/06/2026 à 12:00 (MockClock) : chaque cas place la sortie
 * avant ou après cette date pour vérifier l'état attendu, sans dépendre du jour où le test tourne.
 */
class SortieStateManagerTest extends TestCase
{
    private const MAINTENANT = '2026-06-15 12:00:00';

    /**
     * @param EntityManagerInterface|null $em simulacre fourni par les tests qui vérifient l'enregistrement (flush)
     */
    private function manager(?EntityManagerInterface $em = null): SortieStateManager
    {
        // Le dépôt renvoie un objet Etat portant le libellé demandé, sans base de données.
        $etatRepository = $this->createStub(EtatRepository::class);
        $etatRepository->method('findOneBy')
            ->willReturnCallback(fn (array $criteres) => $this->etat($criteres['libelle']));

        return new SortieStateManager(
            $etatRepository,
            $em ?? $this->createStub(EntityManagerInterface::class),
            new MockClock(self::MAINTENANT)
        );
    }

    public static function casDeCalcul(): iterable
    {
        // [état actuel, début, durée (min), date limite, inscrits, max, état attendu]
        yield 'ouverte : places libres et date limite à venir' =>
            [Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-18', 2, 10, Etat::OUVERTE];
        yield 'date limite le jour même : encore ouverte jusqu\'à 23:59:59' =>
            [Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-15', 2, 10, Etat::OUVERTE];
        yield 'date limite dépassée : clôturée' =>
            [Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-14', 2, 10, Etat::CLOTUREE];
        yield 'nombre maximum atteint : clôturée' =>
            [Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-18', 3, 3, Etat::CLOTUREE];
        yield 'clôturée qui retrouve une place : de nouveau ouverte' =>
            [Etat::CLOTUREE, '2026-06-20 14:00', 120, '2026-06-18', 2, 3, Etat::OUVERTE];
        yield 'commencée il y a une heure : en cours' =>
            [Etat::CLOTUREE, '2026-06-15 11:00', 120, '2026-06-14', 2, 10, Etat::EN_COURS];
        yield 'finie hier : terminée' =>
            [Etat::EN_COURS, '2026-06-14 10:00', 120, '2026-06-13', 2, 10, Etat::TERMINEE];
        yield 'finie il y a plus d\'un mois : historisée' =>
            [Etat::TERMINEE, '2026-05-01 10:00', 120, '2026-04-30', 2, 10, Etat::HISTORISEE];
        yield 'annulée depuis moins d\'un mois : reste annulée' =>
            [Etat::ANNULEE, '2026-06-01 10:00', 120, '2026-05-30', 2, 10, Etat::ANNULEE];
        yield 'annulée depuis plus d\'un mois : historisée' =>
            [Etat::ANNULEE, '2026-05-01 10:00', 120, '2026-04-30', 2, 10, Etat::HISTORISEE];
        yield 'en création : jamais modifiée automatiquement' =>
            [Etat::CREEE, '2026-05-01 10:00', 120, '2026-04-30', 2, 10, Etat::CREEE];
        yield 'historisée : jamais modifiée automatiquement' =>
            [Etat::HISTORISEE, '2026-06-20 14:00', 120, '2026-06-18', 0, 10, Etat::HISTORISEE];
    }

    #[DataProvider('casDeCalcul')]
    public function testUpdateEtatCalculeLEtatAttendu(
        string $etatActuel,
        string $debut,
        int    $duree,
        string $dateLimite,
        int    $inscrits,
        int    $max,
        string $etatAttendu
    ): void
    {
        $sortie = $this->sortie($etatActuel, $debut, $duree, $dateLimite, $inscrits, $max);

        $aChange = $this->manager()->updateEtat($sortie);

        self::assertSame($etatAttendu, $sortie->getEtat()->getLibelle());
        // updateEtat() doit signaler un changement uniquement quand l'état a réellement bougé.
        self::assertSame($etatAttendu !== $etatActuel, $aChange);
    }

    public function testUpdateEtatsEnregistreSeulementSIlYAUnChangement(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->manager($em)->updateEtats([
            $this->sortie(Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-18', 2, 10), // inchangée
            $this->sortie(Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-14', 2, 10), // devient clôturée
        ]);
    }

    public function testUpdateEtatsNEnregistreRienSansChangement(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->manager($em)->updateEtats([
            $this->sortie(Etat::OUVERTE, '2026-06-20 14:00', 120, '2026-06-18', 2, 10),
            $this->sortie(Etat::CREEE, '2026-05-01 10:00', 120, '2026-04-30', 2, 10),
        ]);
    }

    private function sortie(
        string $etat,
        string $debut,
        int    $duree,
        string $dateLimite,
        int    $inscrits,
        int    $max
    ): Sortie
    {
        $sortie = (new Sortie())
            ->setEtat($this->etat($etat))
            ->setDateHeureDebut(new \DateTime($debut))
            ->setDuree($duree)
            ->setDateLimiteInscription(new \DateTime($dateLimite))
            ->setNbInscriptionsMax($max);

        for ($i = 0; $i < $inscrits; $i++) {
            $sortie->addParticipant(new Participant());
        }

        return $sortie;
    }

    private function etat(string $libelle): Etat
    {
        return (new Etat())->setLibelle($libelle);
    }
}
