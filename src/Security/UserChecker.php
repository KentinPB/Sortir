<?php

namespace App\Security;

use App\Entity\Participant;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    /**
     * Vérifie avant authentification que le compte du participant est actif.
     * Un utilisateur désactivé ne peut pas se connecter même avec des identifiants valides.
     */
    public function checkPreAuth(UserInterface $user): void

    {
        if (!$user instanceof Participant) {
            return;
        }

        if (!$user->isActif()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est désactivé. Veuillez contacter un administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Aucun contrôle post-authentification nécessaire actuellement.
    }
}
