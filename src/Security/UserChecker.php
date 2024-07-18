<?php
// src/Security/UserChecker.php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getUserBanned() && $user->getUserBanned()->isBanned()) {
            $bannedUntil = (clone $user->getUserBanned()->getBannedDate())->modify('+7 days');
            throw new CustomUserMessageAuthenticationException(
                'Vous êtes banni de vous connecter jusqu\'au ' . $bannedUntil->format('Y-m-d H:i:s')
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Rien à faire ici
    }
}
