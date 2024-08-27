<?php
// src/Controller/MessageController.php

namespace App\Controller\Messaging; // Déclare l'espace de noms pour ce contrôleur

use App\Entity\Conversation; // Importe l'entité Conversation
use App\Entity\Message; // Importe l'entité Message
use App\Entity\User; // Importe l'entité User
use App\Form\MessageType; // Importe le formulaire MessageType
use App\Repository\ConversationRepository; // Importe le repository pour Conversation
use App\Repository\MessageRepository; // Importe le repository pour Message
use App\Repository\UserRepository; // Importe le repository pour User
use App\Repository\BlockRepository; // Importe le repository pour les blocages entre utilisateurs
use Doctrine\ORM\EntityManagerInterface; // Importe l'interface EntityManager pour la gestion des entités
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importe le contrôleur de base de Symfony
use Symfony\Component\HttpFoundation\Request; // Importe la classe Request de Symfony
use Symfony\Component\HttpFoundation\Response; // Importe la classe Response de Symfony
use Symfony\Component\Routing\Annotation\Route; // Importe l'annotation Route pour définir les routes
use Symfony\Component\Security\Core\Exception\AccessDeniedException; // Importe l'exception AccessDeniedException
use Symfony\Component\Security\Csrf\CsrfToken; // Importe la classe CsrfToken pour la validation des tokens CSRF
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface; // Importe l'interface CsrfTokenManagerInterface

#[Route('/messages')] // Définit la route de base pour ce contrôleur
class MessageController extends AbstractController // La classe MessageController hérite de AbstractController
{
    private $csrfTokenManager; // Déclare la propriété csrfTokenManager
    private $blockRepository; // Déclare la propriété blockRepository

    // Constructeur pour injecter le gestionnaire de tokens CSRF et le repository de blocage
    public function __construct(CsrfTokenManagerInterface $csrfTokenManager, BlockRepository $blockRepository)
    {
        $this->csrfTokenManager = $csrfTokenManager; // Initialise csrfTokenManager
        $this->blockRepository = $blockRepository; // Initialise blockRepository
    }

    #[Route('/', name: 'app_messages')] // Définit une route pour l'index des messages
    public function index(MessageRepository $messageRepository, ConversationRepository $conversationRepository): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté

        // Récupérer les conversations de l'utilisateur
        $conversations = $conversationRepository->findByUser($user);

        // Filtrer les conversations avec les utilisateurs bloqués
        $conversations = array_filter($conversations, function($conversation) use ($user) {
            $otherUser = $conversation->getOtherUser($user); // Récupère l'autre utilisateur de la conversation
            return !$this->blockRepository->isBlocked($user, $otherUser) && !$this->blockRepository->isBlockedBy($user, $otherUser);
            // Retourne vrai si ni l'utilisateur ni l'autre utilisateur ne sont bloqués l'un par l'autre
        });

        $unreadMessagesCount = $messageRepository->countUnreadMessagesForUser($user); // Compte les messages non lus pour l'utilisateur

        // Retourne la vue avec les conversations et le nombre de messages non lus
        return $this->render('messaging/index.html.twig', [
            'conversations' => $conversations,
            'unreadMessagesCount' => $unreadMessagesCount,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_message_conversation')] // Définit une route pour une conversation spécifique
    public function conversation(Conversation $conversation, Request $request, EntityManagerInterface $entityManager, MessageRepository $messageRepository): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté
        $blockMessage = null; // Variable pour stocker le message d'information

        // Vérifiez si l'utilisateur a accès à la conversation
        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw new AccessDeniedException(); // Jette une exception si l'utilisateur n'est pas participant à la conversation
        }

        // Vérifiez si l'utilisateur est bloqué
        $otherUser = $conversation->getOtherUser($user); // Récupère l'autre utilisateur de la conversation
        if ($otherUser === null) {
            throw new AccessDeniedException('Conversation not found for this user.'); // Jette une exception si l'autre utilisateur n'existe pas
        }

        // Vérifie si l'utilisateur ou l'autre utilisateur sont bloqués
        if ($this->blockRepository->isBlocked($user, $otherUser) || $this->blockRepository->isBlockedBy($user, $otherUser)) {
            $blockMessage = 'Vous ne pouvez pas envoyer de messages à cet utilisateur car vous avez été bloqué ou vous avez bloqué cet utilisateur.';
            // Définit un message d'erreur si l'utilisateur ou l'autre utilisateur sont bloqués
        }

        $message = new Message(); // Crée une nouvelle instance de Message
        $form = $this->createForm(MessageType::class, $message); // Crée un formulaire pour le message
        $form->handleRequest($request); // Gère la requête pour le formulaire

        // Marquer les messages non lus comme lus pour l'utilisateur actuel
        $messageRepository->markMessagesAsReadForConversation($user, $conversation);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            if ($blockMessage === null) {
                $recipient = $conversation->getUser1() === $user ? $conversation->getUser2() : $conversation->getUser1(); // Récupère le destinataire

                // Vérifiez si l'utilisateur est bloqué avant d'envoyer le message
                if ($this->blockRepository->isBlocked($user, $recipient) || $this->blockRepository->isBlockedBy($user, $recipient)) {
                    $blockMessage = 'Vous ne pouvez pas envoyer de messages à cet utilisateur car vous avez été bloqué ou vous avez bloqué cet utilisateur.';
                    // Définit un message d'erreur si l'utilisateur ou le destinataire sont bloqués
                } else {
                    // Sauvegarder le message pour l'expéditeur
                    $message->setSender($user); // Définit l'expéditeur
                    $message->setRecipient($recipient); // Définit le destinataire
                    $message->setConversation($conversation); // Associe le message à la conversation
                    $message->setOwner($user); // Définit le propriétaire du message
                    $message->setSentAt(new \DateTime()); // Définit la date d'envoi

                    $entityManager->persist($message); // Persiste le message dans la base de données

                    // Créer une copie du message pour le destinataire
                    $recipientMessage = new Message();
                    $recipientMessage->setContent($message->getContent()); // Copie le contenu du message
                    $recipientMessage->setSender($user); // Définit l'expéditeur
                    $recipientMessage->setRecipient($recipient); // Définit le destinataire
                    $recipientMessage->setConversation($conversation); // Associe le message à la conversation
                    $recipientMessage->setOwner($recipient); // Définit le propriétaire du message
                    $recipientMessage->setSentAt(new \DateTime()); // Définit la date d'envoi

                    $entityManager->persist($recipientMessage); // Persiste la copie du message

                    $entityManager->flush(); // Enregistre les changements dans la base de données

                    return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]); // Redirige vers la conversation
                }
            }
        }

        // Récupérer les messages appartenant à l'utilisateur actuel dans cette conversation
        $messages = $entityManager->getRepository(Message::class)->findBy([
            'conversation' => $conversation,
            'owner' => $user
        ]);

        // Retourne la vue avec les messages et le formulaire
        return $this->render('messaging/conversation.html.twig', [
            'conversation' => $conversation,
            'form' => $form->createView(),
            'messages' => $messages,
            'blockMessage' => $blockMessage, // Passer le message d'information à la vue
        ]);
    }

    #[Route('/new/{username}', name: 'app_message_new')] // Définit une route pour démarrer une nouvelle conversation
    public function new($username, UserRepository $userRepository, ConversationRepository $conversationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté
        $recipient = $userRepository->findOneBy(['username' => $username]); // Récupère l'utilisateur destinataire basé sur le nom d'utilisateur

        if (!$recipient) {
            throw $this->createNotFoundException('Utilisateur non trouvé'); // Jette une exception si le destinataire n'existe pas
        }

        // Vérifiez si l'utilisateur est bloqué
        if ($this->blockRepository->isBlocked($user, $recipient) || $this->blockRepository->isBlockedBy($user, $recipient)) {
            throw new AccessDeniedException('Vous ne pouvez pas envoyer de messages à cet utilisateur.'); // Jette une exception si l'utilisateur ou le destinataire sont bloqués
        }

        $conversation = $conversationRepository->findConversationByUsers($user, $recipient); // Recherche une conversation existante entre les deux utilisateurs

        if (!$conversation) {
            $conversation = new Conversation(); // Crée une nouvelle conversation si elle n'existe pas
            $conversation->setUser1($user); // Définit le premier utilisateur
            $conversation->setUser2($recipient); // Définit le second utilisateur
            $entityManager->persist($conversation); // Persiste la conversation dans la base de données
            $entityManager->flush(); // Enregistre les changements dans la base de données
        }

        // Redirige vers la conversation nouvellement créée ou existante
        return $this->redirectToRoute('app_message_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/conversation/delete/{id}', name: 'app_message_delete_conversation', methods: ['POST'])] // Définit une route pour supprimer une conversation
    public function deleteConversation(Conversation $conversation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté

        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            throw new AccessDeniedException(); // Jette une exception si l'utilisateur n'est pas participant à la conversation
        }

        $token = $request->request->get('_token'); // Récupère le token CSRF depuis la requête
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $conversation->getId(), $token))) {
            throw new AccessDeniedException('Invalid CSRF token'); // Jette une exception si le token CSRF est invalide
        }

        $entityManager->remove($conversation); // Supprime la conversation de la base de données
        $entityManager->flush(); // Enregistre les changements dans la base de données

        // Redirige vers la page des messages
        return $this->redirectToRoute('app_messages');
    }

    #[Route('/message/delete/{id}', name: 'app_message_delete', methods: ['POST'])] // Définit une route pour supprimer un message
    public function deleteMessage(Message $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté

        if ($message->getSender() !== $user && $message->getRecipient() !== $user) {
            throw new AccessDeniedException(); // Jette une exception si l'utilisateur n'est pas l'expéditeur ou le destinataire du message
        }

        $token = $request->request->get('_token'); // Récupère le token CSRF depuis la requête
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $message->getId(), $token))) {
            throw new AccessDeniedException('Invalid CSRF token'); // Jette une exception si le token CSRF est invalide
        }

        $entityManager->remove($message); // Supprime le message de la base de données
        $entityManager->flush(); // Enregistre les changements dans la base de données

        // Redirige vers la conversation d'où le message a été supprimé
        return $this->redirectToRoute('app_message_conversation', ['id' => $message->getConversation()->getId()]);
    }

    #[Route('/messages/mark-as-read/{id}', name: 'app_message_mark_as_read', methods: ['POST'])] // Définit une route pour marquer les messages comme lus
    public function markAsRead(Conversation $conversation, MessageRepository $messageRepository): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté

        if ($conversation->getUser1() !== $user && $conversation->getUser2() !== $user) {
            return $this->json(['success' => false, 'message' => 'Accès refusé'], Response::HTTP_FORBIDDEN); // Retourne une réponse JSON si l'utilisateur n'a pas accès à la conversation
        }

        $messageRepository->markMessagesAsReadForConversation($user, $conversation); // Marque les messages comme lus

        return $this->json(['success' => true]); // Retourne une réponse JSON indiquant le succès de l'opération
    }
}

