<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Lieu;
use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur API exposant la liste des lieux d'une ville,
 * utilisé par le JavaScript du formulaire de sortie pour
 * le filtrage dynamique sans rechargement de page.
 *
 * @author Développeur JS/UX
 *
 */
class LieuApiController extends AbstractController
{
    /**
     * @param int $idVille Identifiant de la ville sélectionnée
     * @param LieuRepository $lieuRepository
     * @return JsonResponse Liste des lieux au format JSON
     */
    #[Route('/api/lieux/{idVille}', name: 'api_lieux_par_ville', methods: ['GET'])]
    public function lieuxParVille(int $idVille, LieuRepository $lieuRepository): JsonResponse
    {
        $lieux = $lieuRepository->createFindByVilleQueryBuilder($idVille)->getQuery()->getResult();

        $data = array_map(static fn(Lieu $lieu) => [
            'id' => $lieu->getId(),
            'nom' => $lieu->getNom(),
            'rue' => $lieu->getRue(),
            'codePostal' => $lieu->getVille()?->getCodePostal() ?? '',
            'latitude' => $lieu->getLatitude(),
            'longitude' => $lieu->getLongitude(),
        ], $lieux);

        return $this->json($data);
    }
}
