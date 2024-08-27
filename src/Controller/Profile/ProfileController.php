<?php
// src/Controller/ProfileController.php

namespace App\Controller\Profile;

// Importation des entités, formulaires et repositories nécessaires pour gérer le profil utilisateur
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\ClubRepository;

// Importation de l'EntityManager pour la gestion des entités
use Doctrine\ORM\EntityManagerInterface;

// Importation des classes de base pour les contrôleurs et les composants HTTP
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route; // Importe l'annotation Route pour définir les routes

// Importation du composant de sécurité pour l'authentification utilisateur
use Symfony\Component\Security\Core\Security;

// Déclaration de la classe ProfileController qui hérite du contrôleur abstrait de Symfony
class ProfileController extends AbstractController
{
    // Route pour accéder à la page de profil de l'utilisateur
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security, ClubRepository $clubRepository): Response
    {
        // Récupération de l'utilisateur connecté
        $user = $security->getUser();
        
        // Récupération du profil utilisateur associé à l'utilisateur connecté
        $userProfile = $user->getUserProfile();

        // Si le profil utilisateur n'existe pas, en créer un nouveau et l'associer à l'utilisateur
        if (!$userProfile) {
            $userProfile = new UserProfile();
            $userProfile->setUser($user);
            $user->setUserProfile($userProfile);
        }

        // Création et gestion du formulaire de profil utilisateur
        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de la mise à jour de l'email si celui-ci a changé
            $newEmail = $form->get('email')->getData();
            if ($newEmail !== $user->getEmail()) {
                $user->setEmail($newEmail);
            }

            // Récupération du club sélectionné dans le formulaire via son ID
            $clubId = $request->request->get('clubId');
            $club = $clubRepository->find($clubId);
            $userProfile->setClub($club);

            // Persistance des modifications du profil utilisateur
            $entityManager->persist($userProfile);
            $entityManager->flush();

            // Ajout d'un message flash pour informer l'utilisateur de la mise à jour réussie
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            // Redirection vers la page de profil
            return $this->redirectToRoute('app_profile');
        }

        // Rendu de la vue du profil utilisateur avec le formulaire
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    // Route pour rechercher des clubs à partir du profil utilisateur
    #[Route('/profile/clubs/search', name: 'profile_club_search', methods: ['GET'])]
    public function searchClubs(Request $request, ClubRepository $clubRepository): JsonResponse
    {
        // Récupère le terme de recherche ('q') depuis la requête ou une chaîne vide si non présent
        $query = $request->query->get('q', '');

        // Recherche des clubs correspondant au terme de recherche
        $clubs = $clubRepository->searchClubsByName($query);

        // Préparation des résultats sous forme de tableau associatif
        $results = [];
        foreach ($clubs as $club) {
            $results[] = [
                'id' => $club->getId(),             // Identifiant du club
                'name' => $club->getName(),         // Nom du club
                'logo' => $club->getLogoClub(),     // Logo du club
            ];
        }

        // Retourne les résultats de la recherche sous forme de JSON
        return $this->json($results);
    }
    
}
