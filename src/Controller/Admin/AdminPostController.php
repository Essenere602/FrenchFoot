<?php
// Déclaration du namespace pour organiser le code. Ici, il est utilisé pour les contrôleurs admin dans l'application.
namespace App\Controller\Admin;

// Importation des classes nécessaires
use App\Entity\Post; // Importation de l'entité Post
use App\Form\PostType; // Importation du formulaire PostType
use Knp\Component\Pager\PaginatorInterface; // Importation de l'interface de pagination
use App\Repository\PostRepository; // Importation du repository pour l'entité Post
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface EntityManager pour les opérations sur la base de données
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour gérer les réponses HTTP
use Symfony\Component\Routing\Attribute\Route; // Importation de l'attribut Route pour définir les routes

// Définition de la route pour ce contrôleur. Les méthodes de ce contrôleur seront accessibles sous '/admin/post'.
#[Route('/admin/post')]
class AdminPostController extends AbstractController
{
    // Route pour la liste des posts, accessible via une requête GET
    #[Route('/', name: 'app_admin_post_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepository, PaginatorInterface $paginator): Response
    {
        // Création d'un QueryBuilder pour l'entité Post, pour construire la requête de sélection
        $queryBuilder = $postRepository->createQueryBuilder('p');

        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle, par défaut 1
            10 // Nombre d'éléments par page
        );

        // Rendu du template Twig avec les résultats paginés
        return $this->render('admin/admin_post/index.html.twig', [
            'pagination' => $pagination, // Passage de l'objet pagination au template
        ]);
    }

    // Route pour créer un nouveau post, accessible via GET (affichage du formulaire) et POST (soumission du formulaire)
    #[Route('/new', name: 'app_admin_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post(); // Création d'une nouvelle instance de Post
        $form = $this->createForm(PostType::class, $post); // Création du formulaire basé sur le type PostType
        $form->handleRequest($request); // Gestion de la requête HTTP avec le formulaire

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($post); // Préparation de l'entité pour la persistance
            $entityManager->flush(); // Enregistrement dans la base de données

            // Redirection vers la liste des posts après la création
            return $this->redirectToRoute('app_admin_post_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig pour le formulaire de création
        return $this->render('admin/admin_post/new.html.twig', [
            'post' => $post, // Passage de l'entité post au template
            'form' => $form, // Passage du formulaire au template
        ]);
    }

    // Route pour afficher un post spécifique, accessible via une requête GET
    #[Route('/{id}', name: 'app_admin_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        // Rendu du template Twig pour afficher les détails du post
        return $this->render('admin/admin_post/show.html.twig', [
            'post' => $post, // Passage de l'entité post au template
        ]);
    }

    // Route pour éditer un post spécifique, accessible via GET (affichage du formulaire) et POST (soumission du formulaire)
    #[Route('/{id}/edit', name: 'app_admin_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post); // Création du formulaire pour l'entité Post existante
        $form->handleRequest($request); // Gestion de la requête HTTP avec le formulaire

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // Enregistrement des modifications dans la base de données

            // Redirection vers la liste des posts après la mise à jour
            return $this->redirectToRoute('app_admin_post_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig pour le formulaire d'édition
        return $this->render('admin/admin_post/edit.html.twig', [
            'post' => $post, // Passage de l'entité post au template
            'form' => $form, // Passage du formulaire au template
        ]);
    }

    // Route pour supprimer un post spécifique, accessible uniquement via une requête POST
    #[Route('/{id}', name: 'app_admin_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        // Vérification du jeton CSRF pour la suppression
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post); // Suppression de l'entité de la base de données
            $entityManager->flush(); // Enregistrement des modifications dans la base de données
        }

        // Redirection vers la liste des posts après la suppression
        return $this->redirectToRoute('app_admin_post_index', [], Response::HTTP_SEE_OTHER);
    }
}
