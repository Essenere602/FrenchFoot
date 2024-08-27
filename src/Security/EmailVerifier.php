<?php

namespace App\Security;

// Importation des classes nécessaires pour la gestion des emails et des entités.
use App\Entity\User; // Importation de l'entité User représentant les utilisateurs.
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface pour la gestion des entités Doctrine.
use Symfony\Bridge\Twig\Mime\TemplatedEmail; // Importation de la classe TemplatedEmail pour les emails avec des modèles Twig.
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour accéder aux données des requêtes HTTP.
use Symfony\Component\Mailer\MailerInterface; // Importation de l'interface MailerInterface pour l'envoi des emails.
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface; // Importation de l'interface pour les exceptions liées à la vérification des emails.
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface; // Importation de l'interface pour l'aide à la vérification des emails.

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper, // Injection du service VerifyEmailHelperInterface pour générer des signatures d'email.
        private MailerInterface $mailer, // Injection du service MailerInterface pour l'envoi des emails.
        private EntityManagerInterface $entityManager // Injection du service EntityManagerInterface pour la gestion des entités.
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        // Génération des composants de signature pour l'email de confirmation.
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName, // Nom de la route de vérification de l'email.
            (string) $user->getId(), // ID de l'utilisateur sous forme de chaîne.
            $user->getEmail() // Email de l'utilisateur.
        );

        // Récupération du contexte de l'email et ajout des informations de signature.
        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl(); // URL signée pour la vérification.
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey(); // Clé du message d'expiration.
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData(); // Données du message d'expiration.

        $email->context($context); // Mise à jour du contexte de l'email avec les nouvelles informations.

        $this->mailer->send($email); // Envoi de l'email.
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        // Validation de la confirmation de l'email à partir de la requête.
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), $user->getEmail());

        // Marquer l'utilisateur comme vérifié.
        $user->setVerified(true);

        $this->entityManager->persist($user); // Persistance de l'utilisateur avec le statut vérifié.
        $this->entityManager->flush(); // Enregistrement des modifications en base de données.
    }
}
