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

    /**
     * Retourne un QueryBuilder pour charger les lieux d'une seule
     * ville donnée, avec la ville en Eager Loading.
     *
     * @param int $idVille Identifiant de la ville sélectionnée
     * @return QueryBuilder
     * @author Développeur JS/UX
     */
    public function createFindByVilleQueryBuilder(int $idVille): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.ville', 'v')
            ->addSelect('v')
            ->andWhere('l.ville = :idVille')
            ->setParameter('idVille', $idVille)
            ->orderBy('l.nom', 'ASC');
    }
}
