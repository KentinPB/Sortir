<?php

namespace App\Repository;

use App\Entity\Lieu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class LieuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lieu::class);
    }

    /**
     * Retourne un QueryBuilder pour charger tous les lieux avec leur ville (Eager Loading)
     */
    public function createFindAllWithVilleQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.ville', 'v')
            ->addSelect('v')
            ->orderBy('l.nom', 'ASC');
    }
}
