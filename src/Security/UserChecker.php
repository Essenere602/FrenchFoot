<?php
namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            $this->logger->debug('UserChecker: L\'utilisateur n\'est pas de type User.');
            return;
        }

        $userBanned = $user->getUserBanned();

        if ($userBanned) {
            $this->logger->debug('UserChecker: User banned info.', [
                'isPermanentlyBanned' => $userBanned->isPermanentlyBanned(),
                'isBanned' => $userBanned->isBanned(),
                'bannedDate' => $userBanned->getBannedDate()
            ]);
        } else {
            $this->logger->debug('UserChecker: L\'utilisateur n\'a pas d\'informations de bannissement.');
        }

        // Vérifie d'abord si l'utilisateur est définitivement banni
        if ($userBanned && $userBanned->isPermanentlyBanned()) {
            $this->logger->info('UserChecker: User is permanently banned.', ['user' => $user->getUsername()]);
            throw new CustomUserMessageAuthenticationException('Votre compte est définitivement banni.');
        }

        // Ensuite, vérifie le bannissement temporaire
        if ($userBanned && $userBanned->isBanned()) {
            $bannedUntil = (clone $userBanned->getBannedDate())->modify('+7 days');
            $this->logger->info('UserChecker: User is temporarily banned.', [
                'user' => $user->getUsername(),
                'bannedUntil' => $bannedUntil->format('Y-m-d H:i:s')
            ]);
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