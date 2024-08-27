<?php
// src/Controller/SecurityController.php

namespace App\Controller\Security;

// Importation des classes nécessaires pour la gestion des utilisateurs et des requêtes.
use App\Entity\User; // Importation de l'entité User représentant les utilisateurs.
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface pour la gestion des entités Doctrine.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour accéder aux données des requêtes HTTP.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\Routing\Annotation\Route; // Importation de l'annotation Route pour la définition des routes.
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils; // Importation de la classe AuthenticationUtils pour la gestion des erreurs de connexion.

class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager; // Déclaration de l'interface pour la gestion des entités.

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager; // Injection du service EntityManagerInterface via le constructeur.
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Récupération de l'erreur de connexion s'il y en a une.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur saisi par l'utilisateur.
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($lastUsername) {
            // Récupération de l'utilisateur depuis la base de données par son nom d'utilisateur.
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['username' => $lastUsername]);

            if ($user && $user->getUserBanned() && $user->getUserBanned()->isBanned()) {
                // Si l'utilisateur est banni, calculer la date de fin du bannissement et préparer un message d'erreur.
                $bannedUntil = (clone $user->getUserBanned()->getBannedDate())->modify('+7 days');
                $error = 'Vous êtes banni de vous connecter jusqu\'au ' . $bannedUntil->format('Y-m-d H:i:s');

                return $this->render('security/login.html.twig', [
                    'last_username' => $lastUsername, // Passage du dernier nom d'utilisateur à la vue.
                    'error' => $error, // Passage de l'erreur à la vue.
                ]);
            }

            // Enregistrement de l'adresse IP de l'utilisateur s'il existe.
            if ($user) {
                $user->setIpAddress($request->getClientIp()); // Récupération et définition de l'adresse IP.
                $this->entityManager->persist($user); // Persistance de l'utilisateur avec la nouvelle adresse IP.
                $this->entityManager->flush(); // Enregistrement des modifications en base de données.
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, // Passage du dernier nom d'utilisateur à la vue.
            'error' => $error, // Passage de l'erreur à la vue.
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode est vide car la logique de déconnexion est gérée par le pare-feu de Symfony.
        throw new \LogicException('Cette méthode peut être vide - elle sera interceptée par la clé logout de votre pare-feu.'); // Exception pour signaler que la méthode doit être interceptée par le pare-feu.
    }
}
