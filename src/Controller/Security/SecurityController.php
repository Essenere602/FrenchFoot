<?php
// src/Controller/SecurityController.php

namespace App\Controller\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($lastUsername) {
            // Récupérer l'utilisateur depuis la base de données
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['username' => $lastUsername]);

            if ($user && $user->getUserBanned() && $user->getUserBanned()->isBanned()) {
                // Utilisateur toujours banni
                $bannedUntil = (clone $user->getUserBanned()->getBannedDate())->modify('+7 days');
                $error = 'Vous êtes banni de vous connecter jusqu\'au ' . $bannedUntil->format('Y-m-d H:i:s');

                return $this->render('security/login.html.twig', [
                    'last_username' => $lastUsername,
                    'error' => $error,
                ]);
            }

            // Enregistrer l'adresse IP de l'utilisateur
            if ($user) {
                $user->setIpAddress($request->getClientIp());
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut être vide - elle sera interceptée par la clé logout de votre pare-feu.');
    }
}

