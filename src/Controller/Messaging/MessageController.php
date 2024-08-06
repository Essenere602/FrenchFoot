<?php
// src/Controller/MessageController.php

namespace App\Controller\Messaging;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\BlockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/messages')]
class MessageController extends AbstractController
{
    private $csrfTokenManager;
    private $blockRepository;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager, BlockRepository $blockRepository)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->blockRepository = $blockRepository;
    }

    #[Route('/', name: 'app_messages')]
    public function index(MessageRepository $messageRepository, ConversationRepository $conversationRepository): Response
    {
        $user = $this->getUser();

        // Récupérer les conversations de l'utilisateur
        $conversations = $conversationRepository->findByUser($user);

        // Filtrer les conversations avec les utilisateurs bloqués
        $conversations = array_filter($conversations, function($conversation) use ($user) {
            $otherUser = $conversation->getOtherUser($user);
            return !$this->blockRepository->isBlocked($user, $otherUser) && !$this->blockRepository->isBlockedBy($user, $otherUser);
        });

        $unreadMessagesCount = $messageRepository->countUnreadMessagesForUser($user);

        return $this->render('messaging/index.html.twig', [
            'conversations' => $conversations,
            'unreadMessagesCount' => $unreadMessagesCount,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_message_conversation')]
    public function conversation(Conversation $conversation, Request $request, EntityManagerInterface $entityManager, MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        $blockMessage = null; // Variable pour stocker le message d'information
    
        // Vérifiez si l'utilisateur a accès à la conversation
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw new AccessDeniedException();
        }
    
        // Vérifiez si l'utilisateur est bloqué
        $otherUser = $conversation->getOtherUser($user);
        if ($otherUser === null) {
            throw new AccessDeniedException('Conversation not found for this user.');
        }
    
        if ($this->blockRepository->isBlocked($user, $otherUser) || $this->blockRepository->isBlockedBy($user, $otherUser)) {
            $blockMessage = 'Vous ne pouvez pas envoyer de messages à cet utilisateur car vous avez été bloqué ou vous avez bloqué cet utilisateur.';
        }
    
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
    
        // Marquer les messages non lus comme lus pour l'utilisateur actuel
        $messageRepository->markMessagesAsReadForConversation($user, $conversation);
    
        if ($form->isSubmitted() && $form->isValid()) {
            if ($blockMessage === null) {
                $recipient = $conversation->getUser1() === $user ? $conversation->getUser2() : $conversation->getUser1();
    
                // Vérifiez si l'utilisateur est bloqué avant d'envoyer le message
                if ($this->blockRepository->isBlocked($user, $recipient) || $this->blockRepository->isBlockedBy($user, $recipient)) {
                    $blockMessage = 'Vous ne pouvez pas envoyer de messages à cet utilisateur car vous avez été bloqué ou vous avez bloqué cet utilisateur.';
                } else {
                    // Sauvegarder le message pour l'expéditeur
                    $message->setSender($user);
                    $message->setRecipient($recipient);
                    $message->setConversation($conversation);
                    $message->setOwner($user);
                    $message->setSentAt(new \DateTime());
    
                    $entityManager->persist($message);
    
                    // Créer une copie du message pour le destinataire
                    $recipientMessage = new Message();
                    $recipientMessage->setContent($message->getContent());
                    $recipientMessage->setSender($user);
                    $recipientMessage->setRecipient($recipient);
                    $recipientMessage->setConversation($conversation);
                    $recipientMessage->setOwner($recipient);
                    $recipientMessage->setSentAt(new \DateTime());
    
                    $entityManager->persist($recipientMessage);
    
                    $entityManager->flush();
    
                    return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]);
                }
            }
        }
    
        // Récupérer les messages appartenant à l'utilisateur actuel dans cette conversation
        $messages = $entityManager->getRepository(Message::class)->findBy([
            'conversation' => $conversation,
            'owner' => $user
        ]);
    
        return $this->render('messaging/conversation.html.twig', [
            'conversation' => $conversation,
            'form' => $form->createView(),
            'messages' => $messages,
            'blockMessage' => $blockMessage, // Passer le message d'information à la vue
        ]);
    }
    
    #[Route('/new/{username}', name: 'app_message_new')]
    public function new($username, UserRepository $userRepository, ConversationRepository $conversationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $recipient = $userRepository->findOneBy(['username' => $username]);

        if (!$recipient) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Vérifiez si l'utilisateur est bloqué
        if ($this->blockRepository->isBlocked($user, $recipient) || $this->blockRepository->isBlockedBy($user, $recipient)) {
            throw new AccessDeniedException('Vous ne pouvez pas envoyer de messages à cet utilisateur.');
        }

        $conversation = $conversationRepository->findConversationByUsers($user, $recipient);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setUser1($user);
            $conversation->setUser2($recipient);
            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/conversation/delete/{id}', name: 'app_message_delete_conversation', methods: ['POST'])]
    public function deleteConversation(Conversation $conversation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw new AccessDeniedException();
        }
    
        $token = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $conversation->getId(), $token))) {
            throw new AccessDeniedException('Invalid CSRF token');
        }
    
        $entityManager->remove($conversation);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_messages');
    }
    
    #[Route('/message/delete/{id}', name: 'app_message_delete', methods: ['POST'])]
    public function deleteMessage(Message $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        if ($message->getSender() !== $user && $message->getRecipient() !== $user) {
            throw new AccessDeniedException();
        }
    
        $token = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $message->getId(), $token))) {
            throw new AccessDeniedException('Invalid CSRF token');
        }
    
        $entityManager->remove($message);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_message_conversation', ['id' => $message->getConversation()->getId()]);
    }
}
