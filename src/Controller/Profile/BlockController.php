<?php

// src/Controller/Profile/BlockController.php

namespace App\Controller\Profile;

// Importation des entités et repositories nécessaires
use App\Entity\Block;
use App\Repository\UserRepository;
use App\Repository\BlockRepository;
use Doctrine\ORM\EntityManagerInterface;

// Importation des classes de base pour les contrôleurs et les composants HTTP
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route; // Importe l'annotation Route pour définir les routes

// Importation du composant de sécurité pour l'authentification utilisateur
use Symfony\Component\Security\Core\Security;

// Déclaration de la classe BlockController qui hérite du contrôleur abstrait de Symfony
class BlockController extends AbstractController
{
    // Route pour gérer les blocs d'utilisateurs depuis le profil
    #[Route('/profile/manage-blocks', name: 'app_manage_blocks')]
    public function manageBlocks(Request $request, Security $security, UserRepository $userRepository, BlockRepository $blockRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur connecté
        $user = $security->getUser();

        // Récupération des utilisateurs bloqués par l'utilisateur connecté
        $blocksInitiated = $user->getBlocksInitiated();

        // Vérification si la requête est de type POST (pour bloquer un utilisateur)
        if ($request->isMethod('POST')) {
            // Récupération du nom d'utilisateur à bloquer depuis la requête
            $blockUsername = $request->request->get('block_username');
            $blockedUser = $userRepository->findOneBy(['username' => $blockUsername]);

            // Vérification si l'utilisateur à bloquer existe
            if ($blockedUser) {
                // Vérification si l'utilisateur à bloquer n'est pas un administrateur
                if (in_array('ROLE_ADMIN', $blockedUser->getRoles())) {
                    $this->addFlash('error', 'Vous ne pouvez pas bloquer un administrateur.');
                } else {
                    // Création d'une nouvelle entité Block pour représenter le blocage
                    $block = new Block();
                    $block->setBlocker($user);
                    $block->setBlocked($blockedUser);
                    
                    // Persistance et enregistrement du blocage en base de données
                    $entityManager->persist($block);
                    $entityManager->flush();

                    $this->addFlash('success', 'Utilisateur bloqué avec succès.');
                }
            } else {
                $this->addFlash('error', 'Utilisateur introuvable.');
            }

            // Redirection vers la page de gestion des blocs après l'action
            return $this->redirectToRoute('app_manage_blocks');
        }

        // Rendu de la vue de gestion des blocs avec les données des utilisateurs bloqués
        return $this->render('profile/manage_blocks.html.twig', [
            'blocksInitiated' => $blocksInitiated,
        ]);
    }

    // Route pour débloquer un utilisateur spécifique
    #[Route('/profile/unblock/{id}', name: 'app_unblock_user')]
    public function unblockUser(int $id, Security $security, BlockRepository $blockRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur connecté
        $user = $security->getUser();

        // Récupération de l'entité Block correspondant à l'utilisateur bloqué
        $block = $blockRepository->find($id);

        // Vérification si le blocage existe et si c'est bien l'utilisateur connecté qui a initié le blocage
        if ($block && $block->getBlocker() === $user) {
            // Suppression du blocage de la base de données
            $entityManager->remove($block);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur débloqué avec succès.');
        } else {
            $this->addFlash('error', 'Action non autorisée.');
        }

        // Redirection vers la page de gestion des blocs après l'action
        return $this->redirectToRoute('app_manage_blocks');
    }

    // Route pour l'autocomplétion de noms d'utilisateurs (recherche AJAX)
    #[Route('/profile/user-autocomplete', name: 'user_autocomplete')]
    public function userAutocomplete(Request $request, UserRepository $userRepository): JsonResponse
    {
        // Récupère le terme de recherche ('q') depuis la requête ou une chaîne vide si non présent
        $query = $request->query->get('q', '');

        // Recherche des utilisateurs dont le nom d'utilisateur correspond au terme de recherche
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Préparation des résultats sous forme de tableau associatif
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ];
        }

        // Retourne les résultats de la recherche sous forme de JSON
        return $this->json($results);
    }
}
