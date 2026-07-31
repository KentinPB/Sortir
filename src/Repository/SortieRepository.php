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

    /**
     * Récupère la liste des sorties en fonction des filtres de la page d'accueil.
     *
     * @return Sortie[] Returns an array of Sortie objects
     */
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
