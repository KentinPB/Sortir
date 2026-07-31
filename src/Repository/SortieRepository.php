<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByFiltres(
        ?string $campus, ?string $nom, ?string $dateDebut, ?string $dateFin,
                $user, bool $estOrganisateur, bool $estInscrit, bool $estNonInscrit, bool $sortiesTerminees
    ): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'e')
            ->orderBy('s.dateHeureDebut', 'ASC');

        if ($campus) {
            $qb->andWhere('s.siteOrganisateur = :campus')
                ->setParameter('campus', $campus);
        }

        if ($nom) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($dateDebut) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }

        if ($dateFin) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin));
        }

        if ($estOrganisateur) {
            $qb->andWhere('s.organisateur = :user')
                ->setParameter('user', $user);
        }

        if ($estInscrit) {
            $qb->andWhere(':user MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($estNonInscrit) {
            $qb->andWhere(':user NOT MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if (!$sortiesTerminees) {
            $qb->andWhere('e.libelle != :terminee')
                ->setParameter('terminee', 'Terminee');
        }

        return $qb->getQuery()->getResult();
    }
}
