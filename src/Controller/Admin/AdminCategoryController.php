<?php

namespace App\Controller\Admin; // Déclare le namespace pour ce contrôleur sous le dossier Admin.

use App\Entity\Category; // Importe l'entité Category.
use App\Form\CategoryType; // Importe le formulaire associé à l'entité Category.
use App\Repository\CategoryRepository; // Importe le repository pour l'entité Category.
use Knp\Component\Pager\PaginatorInterface; // Importe l'interface du composant de pagination KnpPaginator.
use Doctrine\ORM\EntityManagerInterface; // Importe l'interface pour la gestion des entités avec Doctrine.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importe le contrôleur de base Symfony.
use Symfony\Component\HttpFoundation\Request; // Importe l'objet Request pour gérer les requêtes HTTP.
use Symfony\Component\HttpFoundation\Response; // Importe l'objet Response pour gérer les réponses HTTP.
use Symfony\Component\Routing\Attribute\Route; // Importe l'attribut Route pour définir les routes.

#[Route('/admin/category')] // Définit le préfixe de route pour ce contrôleur.
class AdminCategoryController extends AbstractController // Déclare la classe AdminCategoryController qui hérite d'AbstractController.
{
    #[Route('/', name: 'app_admin_category_index', methods: ['GET'])] // Définit la route pour la page d'index des catégories, accessible via GET.
    public function index(Request $request, CategoryRepository $categoryRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $categoryRepository->createQueryBuilder('c'); // Crée un QueryBuilder pour récupérer les catégories depuis la base de données.

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données à paginer.
            $request->query->getInt('page', 1), // Le numéro de la page actuelle, par défaut 1.
            10 // Nombre d'éléments par page.
        );

        return $this->render('admin/admin_category/index.html.twig', [ // Retourne la vue avec la pagination.
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])] // Définit la route pour créer une nouvelle catégorie, accessible via GET et POST.
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category(); // Crée une nouvelle instance de l'entité Category.
        $form = $this->createForm(CategoryType::class, $category); // Crée un formulaire pour l'entité Category.
        $form->handleRequest($request); // Gère la requête et associe les données du formulaire à l'entité.

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide.
            $entityManager->persist($category); // Persiste la nouvelle catégorie dans la base de données.
            $entityManager->flush(); // Exécute les changements dans la base de données.

            return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des catégories.
        }

        return $this->render('admin/admin_category/new.html.twig', [ // Retourne la vue du formulaire pour créer une nouvelle catégorie.
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_category_show', methods: ['GET'])] // Définit la route pour afficher une catégorie spécifique, accessible via GET.
    public function show(Category $category): Response
    {
        return $this->render('admin/admin_category/show.html.twig', [ // Retourne la vue pour afficher les détails de la catégorie.
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])] // Définit la route pour éditer une catégorie, accessible via GET et POST.
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category); // Crée un formulaire pour l'entité Category à éditer.
        $form->handleRequest($request); // Gère la requête et associe les données du formulaire à l'entité.

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide.
            $entityManager->flush(); // Exécute les changements dans la base de données.

            return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des catégories.
        }

        return $this->render('admin/admin_category/edit.html.twig', [ // Retourne la vue du formulaire pour éditer la catégorie.
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_category_delete', methods: ['POST'])] // Définit la route pour supprimer une catégorie, accessible via POST.
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) { // Vérifie la validité du token CSRF.
            $entityManager->remove($category); // Supprime la catégorie de la base de données.
            $entityManager->flush(); // Exécute les changements dans la base de données.
        }

        return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des catégories.
    }
}
