<?php
// src/Twig/AppExtension.php

// Déclaration du namespace pour la classe, elle se trouve dans le dossier Twig.
namespace App\Twig;

// Importation des classes nécessaires pour gérer les messages, les conversations et la sécurité.
use App\Repository\MessageRepository;
use App\Entity\Conversation;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// La classe AppExtension étend AbstractExtension pour ajouter des fonctions Twig personnalisées.
class AppExtension extends AbstractExtension
{
    // Déclaration des propriétés pour les dépendances du repository de messages et la sécurité.
    private $messageRepository;
    private $security;

    // Constructeur pour injecter les dépendances MessageRepository et Security.
    public function __construct(MessageRepository $messageRepository, Security $security)
    {
        $this->messageRepository = $messageRepository;
        $this->security = $security;
    }

    // Méthode pour retourner les fonctions Twig personnalisées.
    public function getFunctions(): array
    {
        return [
            // Déclaration d'une fonction Twig 'unread_messages_count' qui appelle la méthode getUnreadMessagesCount.
            new TwigFunction('unread_messages_count', [$this, 'getUnreadMessagesCount']),
            // Déclaration d'une fonction Twig 'unread_messages_for_conversation' qui appelle la méthode getUnreadMessagesForConversation.
            new TwigFunction('unread_messages_for_conversation', [$this, 'getUnreadMessagesForConversation']),
        ];
    }

    // Méthode pour obtenir le nombre de messages non lus pour l'utilisateur actuel.
    public function getUnreadMessagesCount(): int
    {
        // Récupération de l'utilisateur actuel.
        $user = $this->security->getUser();
        if ($user) {
            // Retourne le nombre de messages non lus pour l'utilisateur.
            return $this->messageRepository->countUnreadMessagesForUser($user);
        }
        // Retourne 0 si aucun utilisateur n'est connecté.
        return 0;
    }

    // Méthode pour obtenir le nombre de messages non lus pour une conversation spécifique.
    public function getUnreadMessagesForConversation(Conversation $conversation): int
    {
        // Récupération de l'utilisateur actuel.
        $user = $this->security->getUser();
        if ($user) {
            // Retourne le nombre de messages non lus pour l'utilisateur dans la conversation donnée.
            return $this->messageRepository->countUnreadMessagesForConversation($user, $conversation);
        }
        // Retourne 0 si aucun utilisateur n'est connecté.
        return 0;
    }
}
