<?php
namespace App\Controller\Admin; // Déclare l'espace de noms pour le contrôleur Admin

use App\Entity\UserBanned; // Importe l'entité UserBanned
use App\Form\UserBannedType; // Importe le formulaire UserBannedType
use App\Repository\UserBannedRepository; // Importe le repository UserBannedRepository
use Doctrine\ORM\EntityManagerInterface; // Importe l'interface EntityManager pour la gestion des entités
use Knp\Component\Pager\PaginatorInterface; // Importe le composant Paginator pour la pagination
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importe le contrôleur de base de Symfony
use Symfony\Component\HttpFoundation\Request; // Importe la classe Request de Symfony
use Symfony\Component\HttpFoundation\Response; // Importe la classe Response de Symfony
use Symfony\Component\Routing\Attribute\Route; // Importe l'attribut Route pour définir les routes

#[Route('/admin/banned')] // Définit la route de base pour ce contrôleur
class AdminBannedController extends AbstractController // La classe AdminBannedController hérite de AbstractController
{
    #[Route('/', name: 'app_admin_banned_index', methods: ['GET'])] // Route pour la liste des utilisateurs bannis
    public function index(Request $request, UserBannedRepository $userBannedRepository, PaginatorInterface $paginator): Response
    {
        // On récupère tous les banissements via un QueryBuilder
        $queryBuilder = $userBannedRepository->createQueryBuilder('ub');

        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle
            10 // Nombre d'éléments par page
        );

        // Rend la vue avec la pagination
        return $this->render('admin/admin_banned/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_banned_new', methods: ['GET', 'POST'])] // Route pour créer un nouvel utilisateur banni
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userBanned = new UserBanned(); // Crée une nouvelle instance de UserBanned
        $form = $this->createForm(UserBannedType::class, $userBanned); // Crée un formulaire lié à UserBanned
        $form->handleRequest($request); // Gère la requête du formulaire

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userBanned); // Persiste l'entité dans la base de données
            $entityManager->flush(); // Enregistre les changements dans la base de données

            return $this->redirectToRoute('app_admin_banned_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la liste des utilisateurs bannis
        }

        // Rend la vue du formulaire de création
        return $this->render('admin/admin_banned/new.html.twig', [
            'user_banned' => $userBanned,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_banned_show', methods: ['GET'])] // Route pour afficher un utilisateur banni
    public function show(UserBanned $userBanned): Response
    {
        // Rend la vue pour afficher les détails de l'utilisateur banni
        return $this->render('admin/admin_banned/show.html.twig', [
            'user_banned' => $userBanned,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_banned_edit', methods: ['GET', 'POST'])] // Route pour éditer un utilisateur banni
    public function edit(Request $request, UserBanned $userBanned, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserBannedType::class, $userBanned); // Crée un formulaire pour éditer UserBanned
        $form->handleRequest($request); // Gère la requête du formulaire

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // Enregistre les changements dans la base de données

            return $this->redirectToRoute('app_admin_banned_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la liste des utilisateurs bannis
        }

        // Rend la vue du formulaire d'édition
        return $this->render('admin/admin_banned/edit.html.twig', [
            'user_banned' => $userBanned,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_banned_delete', methods: ['POST'])] // Route pour supprimer un utilisateur banni
    public function delete(Request $request, UserBanned $userBanned, EntityManagerInterface $entityManager): Response
    {
        // Validation du token CSRF pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete'.$userBanned->getId(), $request->request->get('_token'))) {
            $entityManager->remove($userBanned); // Supprime l'utilisateur banni
            $entityManager->flush(); // Enregistre les changements dans la base de données
        }

        // Redirige vers la liste des utilisateurs bannis
        return $this->redirectToRoute('app_admin_banned_index', [], Response::HTTP_SEE_OTHER);
    }
}

