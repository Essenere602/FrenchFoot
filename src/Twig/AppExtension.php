<?php
// src/Twig/AppExtension.php

namespace App\Twig;

use App\Repository\MessageRepository;
use App\Entity\Conversation;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $messageRepository;
    private $security;

    public function __construct(MessageRepository $messageRepository, Security $security)
    {
        $this->messageRepository = $messageRepository;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_messages_count', [$this, 'getUnreadMessagesCount']),
            new TwigFunction('unread_messages_for_conversation', [$this, 'getUnreadMessagesForConversation']),
        ];
    }

    public function getUnreadMessagesCount(): int
    {
        $user = $this->security->getUser();
        if ($user) {
            return $this->messageRepository->countUnreadMessagesForUser($user);
        }
        return 0;
    }

    public function getUnreadMessagesForConversation(Conversation $conversation): int
    {
        $user = $this->security->getUser();
        if ($user) {
            return $this->messageRepository->countUnreadMessagesForConversation($user, $conversation);
        }
        return 0;
    }
}


