<?php
namespace App\Security;

// Importation des classes nécessaires pour la vérification des utilisateurs et la gestion des logs.
use App\Entity\User; // Importation de l'entité User représentant les utilisateurs.
use Psr\Log\LoggerInterface; // Importation de l'interface LoggerInterface pour les opérations de logging.
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException; // Importation de l'exception pour les erreurs d'authentification avec message personnalisé.
use Symfony\Component\Security\Core\User\UserCheckerInterface; // Importation de l'interface UserCheckerInterface pour la vérification des utilisateurs.
use Symfony\Component\Security\Core\User\UserInterface; // Importation de l'interface UserInterface pour les utilisateurs.

class UserChecker implements UserCheckerInterface
{
    private $logger; // Propriété pour le service de logging.

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger; // Initialisation du service de logging via le constructeur.
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            // Log le message si l'utilisateur n'est pas une instance de User.
            $this->logger->debug('UserChecker: L\'utilisateur n\'est pas de type User.');
            return; // Quitte la méthode si l'utilisateur n'est pas de type User.
        }

        if (!$user->isVerified()) {
            // Log le message si l'utilisateur n'est pas vérifié et lance une exception.
            $this->logger->info('UserChecker: User is not verified.', ['user' => $user->getUsername()]);
            throw new CustomUserMessageAuthenticationException('Votre compte n\'est pas vérifié.');
        }

        $userBanned = $user->getUserBanned(); // Récupération des informations de bannissement de l'utilisateur.

        if ($userBanned) {
            // Log les informations de bannissement si elles existent.
            $this->logger->debug('UserChecker: User banned info.', [
                'isPermanentlyBanned' => $userBanned->isPermanentlyBanned(),
                'isBanned' => $userBanned->isBanned(),
                'bannedDate' => $userBanned->getBannedDate()
            ]);
        } else {
            // Log un message si l'utilisateur n'a pas d'informations de bannissement.
            $this->logger->debug('UserChecker: L\'utilisateur n\'a pas d\'informations de bannissement.');
        }

        // Vérifie si l'utilisateur est définitivement banni.
        if ($userBanned && $userBanned->isPermanentlyBanned()) {
            // Log le message et lance une exception si l'utilisateur est définitivement banni.
            $this->logger->info('UserChecker: User is permanently banned.', ['user' => $user->getUsername()]);
            throw new CustomUserMessageAuthenticationException('Votre compte est définitivement banni.');
        }

        // Vérifie le bannissement temporaire.
        if ($userBanned && $userBanned->isBanned()) {
            // Calcule la date jusqu'à laquelle l'utilisateur est banni.
            $bannedUntil = (clone $userBanned->getBannedDate())->modify('+7 days');
            // Log le message et lance une exception si l'utilisateur est temporairement banni.
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
        // Rien à faire ici, la vérification post-authentification n'est pas nécessaire pour ce contrôleur.
    }
}
