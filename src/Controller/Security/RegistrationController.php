<?php

// Déclaration du namespace du contrôleur, il se trouve dans le dossier Security.
namespace App\Controller\Security;

// Importation des classes nécessaires pour la gestion de l'enregistrement et de la vérification d'email.
use App\Entity\User; // Importation de l'entité User qui représente les utilisateurs.
use App\Form\RegistrationFormType; // Importation du type de formulaire pour l'enregistrement.
use App\Security\EmailVerifier; // Importation du service EmailVerifier pour la vérification des emails.
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface pour la gestion des entités Doctrine.
use Symfony\Bridge\Twig\Mime\TemplatedEmail; // Importation de la classe TemplatedEmail pour l'envoi d'emails avec un modèle Twig.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour accéder aux données des requêtes HTTP.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\Mime\Address; // Importation de la classe Address pour spécifier les adresses email.
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Importation de l'interface pour le hashage des mots de passe des utilisateurs.
use Symfony\Component\Routing\Attribute\Route; // Importation de l'attribut Route pour la définition des routes.
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface; // Importation de l'interface pour les exceptions de vérification d'email.

// Le contrôleur gère l'enregistrement et la vérification des emails pour les utilisateurs.
class RegistrationController extends AbstractController
{
    // Injection de la dépendance pour le service de vérification d'email.
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    // Route pour gérer l'inscription d'un utilisateur.
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Création d'une nouvelle instance de l'entité User.
        $user = new User();
        
        // Création et gestion du formulaire d'inscription avec les données de l'utilisateur.
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, traitement de l'inscription.
        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du mot de passe en utilisant l'interface UserPasswordHasherInterface.
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData() // Récupération du mot de passe en clair depuis le formulaire.
                )
            );

            // Enregistrement de l'utilisateur en base de données.
            $entityManager->persist($user); // Préparation de l'entité pour l'enregistrement.
            $entityManager->flush(); // Enregistrement des modifications en base de données.

            // Génération d'une URL signée et envoi d'un email de confirmation à l'utilisateur.
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail()) // Création d'un nouvel email avec un modèle Twig.
                    ->from(new Address('rahamniasamuel@gmail.com', 'Samuel French\'Foot')) // Définition de l'expéditeur de l'email.
                    ->to($user->getEmail()) // Définition du destinataire de l'email.
                    ->subject('Please Confirm your Email') // Définition du sujet de l'email.
                    ->htmlTemplate('security/registration/confirmation_email.html.twig') // Définition du modèle Twig pour le corps de l'email.
            );

            // Redirection vers la page d'accueil après l'inscription.
            return $this->redirectToRoute('app_homepage');
        }

        // Affichage du formulaire d'inscription.
        return $this->render('security/registration/register.html.twig', [
            'registrationForm' => $form, // Passage du formulaire à la vue pour affichage.
        ]);
    }

    // Route pour vérifier l'email de l'utilisateur après l'inscription.
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        // Assurer que l'utilisateur est entièrement authentifié.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); // Vérification de l'authentification complète de l'utilisateur.

        // Validation du lien de confirmation d'email et mise à jour du statut de l'utilisateur.
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser()); // Traitement de la confirmation de l'email.
        } catch (VerifyEmailExceptionInterface $exception) {
            // En cas d'erreur, affichage d'un message flash et redirection vers la page d'inscription.
            $this->addFlash('verify_email_error', $exception->getReason()); // Affichage d'un message d'erreur.

            return $this->redirectToRoute('app_register'); // Redirection vers la page d'inscription en cas d'erreur.
        }

        // Affichage d'un message de succès après la vérification de l'email.
        $this->addFlash('success', 'Your email address has been verified.'); // Affichage d'un message de succès.

        // Redirection vers la page d'inscription après succès (à modifier si nécessaire).
        return $this->redirectToRoute('app_register');
    }
}
