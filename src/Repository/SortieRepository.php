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
    /**
     * Récupère une sortie spécifique par son ID avec uniquement les relations
     * nécessaires pour sa modification (Campus et Lieu).
     *
     * @param int $id L'identifiant de la sortie
     * @return Sortie|null
     */
    public function findOneForEdit(int $id): ?Sortie
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.siteOrganisateur', 'c')
            ->addSelect('c')
            ->leftJoin('s.lieu', 'l')
            ->addSelect('l')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère une sortie spécifique par son ID avec uniquement les relations
     * nécessaires pour son annulation (État, Campus, Lieu et Ville).
     *
     * @param int $id L'identifiant de la sortie
     * @return Sortie|null
     */
    public function findOneForCancel(int $id): ?Sortie
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.siteOrganisateur', 'c')
            ->addSelect('c')
            ->leftJoin('s.etat', 'e')
            ->addSelect('e')
            ->leftJoin('s.lieu', 'l')
            ->addSelect('l')
            ->leftJoin('l.ville', 'v')
            ->addSelect('v')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
