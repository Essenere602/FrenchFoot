<?php
// src/Controller/ProfileController.php

namespace App\Controller\Profile;

use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security, ClubRepository $clubRepository): Response
    {
        $user = $security->getUser();
        $userProfile = $user->getUserProfile();

        if (!$userProfile) {
            $userProfile = new UserProfile();
            $userProfile->setUser($user);
            $user->setUserProfile($userProfile);
        }

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de la mise à jour de l'email
            $newEmail = $form->get('email')->getData();
            if ($newEmail !== $user->getEmail()) {
                $user->setEmail($newEmail);
            }

            // Récupérer le club à partir de l'ID envoyé par le formulaire
            $clubId = $request->request->get('clubId');
            $club = $clubRepository->find($clubId);
            $userProfile->setClub($club);

            $entityManager->persist($userProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }


    
// Déclaration d'une route pour cette méthode. Elle est accessible via l'URL '/profile/clubs/search', 
// elle est nommée 'profile_club_search' et elle répond uniquement aux requêtes HTTP GET.
#[Route('/profile/clubs/search', name: 'profile_club_search', methods: ['GET'])]
public function searchClubs(Request $request, ClubRepository $clubRepository): JsonResponse

    {
    // Récupère la valeur du paramètre de requête 'q' (recherche) ou une chaîne vide si ce paramètre n'est pas présent
    $query = $request->query->get('q', '');

    // Utilise le repository des clubs pour rechercher des clubs en fonction de la requête
    $clubs = $clubRepository->searchClubsByName($query);

    // Initialise un tableau vide pour stocker les résultats de la recherche
    $results = [];
    
    // Parcourt chaque club trouvé dans la recherche
    foreach ($clubs as $club) {
        // Ajoute les détails du club dans le tableau des résultats
        $results[] = [
            'id' => $club->getId(),             // Récupère l'identifiant du club
            'name' => $club->getName(),         // Récupère le nom du club
            'logo' => $club->getLogoClub(),     // Récupère le logo du club
        ];
    }

    // Retourne les résultats sous forme de réponse JSON
    return $this->json($results);
    }
    
}