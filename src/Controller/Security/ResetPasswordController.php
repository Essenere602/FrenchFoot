<?php

namespace App\Controller\Security;

// Importation des classes nécessaires pour la gestion des mots de passe et des emails.
use App\Entity\User; // Importation de l'entité User représentant les utilisateurs.
use App\Form\ChangePasswordFormType; // Importation du type de formulaire pour le changement de mot de passe.
use App\Form\ResetPasswordRequestFormType; // Importation du type de formulaire pour la demande de réinitialisation du mot de passe.
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface pour la gestion des entités Doctrine.
use Symfony\Bridge\Twig\Mime\TemplatedEmail; // Importation de la classe TemplatedEmail pour l'envoi d'emails avec un modèle Twig.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.
use Symfony\Component\HttpFoundation\RedirectResponse; // Importation de la classe RedirectResponse pour les redirections HTTP.
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour accéder aux données des requêtes HTTP.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\Mailer\MailerInterface; // Importation de l'interface MailerInterface pour l'envoi d'emails.
use Symfony\Component\Mime\Address; // Importation de la classe Address pour spécifier les adresses email.
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Importation de l'interface pour le hashage des mots de passe des utilisateurs.
use Symfony\Component\Routing\Attribute\Route; // Importation de l'attribut Route pour la définition des routes.
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait; // Importation du trait pour gérer la réinitialisation du mot de passe.
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface; // Importation de l'interface pour les exceptions de réinitialisation de mot de passe.
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface; // Importation de l'interface pour la gestion des tokens de réinitialisation de mot de passe.

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait; // Utilisation du trait pour la gestion de la réinitialisation du mot de passe.

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper, // Injection du service ResetPasswordHelperInterface pour gérer les tokens de réinitialisation.
        private EntityManagerInterface $entityManager // Injection du service EntityManagerInterface pour la gestion des entités.
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class); // Création du formulaire de demande de réinitialisation du mot de passe.
        $form->handleRequest($request); // Traitement des données envoyées par la requête.

        if ($form->isSubmitted() && $form->isValid()) { // Vérification si le formulaire est soumis et valide.
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(), // Récupération de l'email depuis le formulaire.
                $mailer // Injection du service MailerInterface pour l'envoi d'emails.
            );
        }

        return $this->render('security/reset_password/request.html.twig', [
            'requestForm' => $form, // Passage du formulaire à la vue pour affichage.
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Génération d'un token factice si l'utilisateur n'existe pas ou si quelqu'un accède directement à cette page.
        // Cela empêche de révéler si un utilisateur a été trouvé avec l'adresse email donnée ou non.
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken(); // Génération d'un token factice.
        }

        return $this->render('security/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken, // Passage du token à la vue pour affichage.
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, string $token = null): Response
    {
        if ($token) { // Si un token est fourni dans l'URL.
            // Stockage du token dans la session et suppression de l'URL pour éviter que le token soit exposé à du JavaScript tiers.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password'); // Redirection vers la même route sans le token dans l'URL.
        }

        $token = $this->getTokenFromSession(); // Récupération du token depuis la session.
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.'); // Exception si aucun token n'est trouvé.
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token); // Validation du token et récupération de l'utilisateur associé.
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, // Message d'erreur en cas de problème de validation du token.
                $e->getReason() // Récupération du motif de l'échec.
            ));

            return $this->redirectToRoute('app_forgot_password_request'); // Redirection vers la page de demande de réinitialisation en cas d'erreur.
        }

        // Le token est valide ; permet à l'utilisateur de changer son mot de passe.
        $form = $this->createForm(ChangePasswordFormType::class); // Création du formulaire de changement de mot de passe.
        $form->handleRequest($request); // Traitement des données envoyées par la requête.

        if ($form->isSubmitted() && $form->isValid()) { // Vérification si le formulaire est soumis et valide.
            // Un token de réinitialisation de mot de passe ne doit être utilisé qu'une seule fois, le supprimer.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encodage (hashage) du mot de passe en clair et définition du nouveau mot de passe pour l'utilisateur.
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData() // Récupération du nouveau mot de passe depuis le formulaire.
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush(); // Enregistrement des modifications en base de données.

            // Nettoyage de la session après la réinitialisation du mot de passe.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_homepage'); // Redirection vers la page d'accueil après la réinitialisation.
        }

        return $this->render('security/reset_password/reset.html.twig', [
            'resetForm' => $form, // Passage du formulaire à la vue pour affichage.
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData, // Recherche de l'utilisateur par email.
        ]);

        // Ne pas révéler si un compte utilisateur a été trouvé ou non.
        if (!$user) {
            return $this->redirectToRoute('app_check_email'); // Redirection vers la page de vérification de l'email.
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user); // Génération du token de réinitialisation pour l'utilisateur.
        } catch (ResetPasswordExceptionInterface $e) {
            // Si vous souhaitez informer l'utilisateur des raisons pour lesquelles un email de réinitialisation n'a pas été envoyé, décommentez les lignes ci-dessous
            // et changez la redirection vers 'app_forgot_password_request'. Attention : cela peut révéler si un utilisateur est enregistré ou non.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE,
            //     $e->getReason()
            // ));

            return $this->redirectToRoute('app_check_email'); // Redirection vers la page de vérification de l'email.
        }

        $email = (new TemplatedEmail()) // Création d'un nouvel email avec un modèle Twig.
            ->from(new Address('samuel.rahamnia@gmail.com', 'Samuel French\'Foot')) // Définition de l'expéditeur de l'email.
            ->to($user->getEmail()) // Définition du destinataire de l'email.
            ->subject('Your password reset request') // Définition du sujet de l'email.
            ->htmlTemplate('security/reset_password/email.html.twig') // Définition du modèle Twig pour le corps de l'email.
            ->context([
                'resetToken' => $resetToken, // Passage du token de réinitialisation au modèle Twig.
            ])
        ;

        $mailer->send($email); // Envoi de l'email.

        // Stockage de l'objet token dans la session pour récupération dans la route de vérification d'email.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email'); // Redirection vers la page de vérification de l'email.
    }
}
