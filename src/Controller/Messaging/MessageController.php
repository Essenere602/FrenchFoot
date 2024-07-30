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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/messages')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages')]
    public function index(MessageRepository $messageRepository, ConversationRepository $conversationRepository): Response
    {
        $user = $this->getUser();

        $conversations = $conversationRepository->findByUser($user);

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
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw new AccessDeniedException();
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        // Marquer les messages non lus comme lus pour l'utilisateur actuel
        $messageRepository->markMessagesAsReadForConversation($user, $conversation);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipient = $conversation->getUser1() === $user ? $conversation->getUser2() : $conversation->getUser1();

            // Sauvegarder le message pour l'expéditeur
            $message->setSender($user);
            $message->setRecipient($recipient);
            $message->setConversation($conversation);
            $message->setOwner($user);
            $message->setSentAt(new \DateTime()); // Définir la date d'envoi

            $entityManager->persist($message);

            // Créer une copie du message pour le destinataire
            $recipientMessage = new Message();
            $recipientMessage->setContent($message->getContent());
            $recipientMessage->setSender($user);
            $recipientMessage->setRecipient($recipient);
            $recipientMessage->setConversation($conversation);
            $recipientMessage->setOwner($recipient);
            $recipientMessage->setSentAt(new \DateTime()); // Définir la date d'envoi

            $entityManager->persist($recipientMessage);

            $entityManager->flush();

            return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]);
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
}
