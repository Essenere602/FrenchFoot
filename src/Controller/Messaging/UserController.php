<?php

// src/Controller/UserController.php

namespace App\Controller\Messaging;

// Importation du UserRepository pour interagir avec les données des utilisateurs
use App\Repository\UserRepository;

// Importation du contrôleur de base Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Importation des composants HTTP pour gérer les réponses JSON et les requêtes HTTP
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

// Importation des annotations de routing pour définir les routes
use Symfony\Component\Routing\Annotation\Route;

// Déclaration du contrôleur UserController qui hérite du contrôleur abstrait de Symfony
#[Route('/user')]
class UserController extends AbstractController
{
    // Route pour la fonctionnalité d'autocomplétion des noms d'utilisateurs
    #[Route('/autocomplete', name: 'app_user_autocomplete')]
    public function autocomplete(Request $request, UserRepository $userRepository): JsonResponse
    {
        // Récupération du terme de recherche depuis la requête
        $term = $request->query->get('term');
        
        // Recherche des utilisateurs dont le nom d'utilisateur correspond au terme recherché
        $users = $userRepository->findByUsernameLike($term);

        // Préparation des résultats pour la réponse JSON
        $results = [];
        foreach ($users as $user) {
            $results[] = ['id' => $user->getId(), 'username' => $user->getUsername()];
        }

        // Retourne une réponse JSON contenant les résultats de l'autocomplétion
        return new JsonResponse($results);
    }
}
