<?php

// src/Controller/Forum/ForumController.php

namespace App\Controller\Forum;

// Importation des différentes entités utilisées dans ce contrôleur
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\UserReport;

// Importation des formulaires associés aux entités
use App\Form\PostType;
use App\Form\TopicType;
use App\Form\UserReportType;

// Importation des repositories pour accéder aux données des entités
use App\Repository\CardRepository;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;

// Importation du gestionnaire d'entités de Doctrine
use Doctrine\ORM\EntityManagerInterface;

// Importation du composant de pagination KnpPaginator
use Knp\Component\Pager\PaginatorInterface;

// Importation du contrôleur de base Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Importation des composants de sécurité et de gestion des requêtes HTTP
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Importation des annotations de routing pour définir les routes
use Symfony\Component\Routing\Annotation\Route;

// Importation de l'exception pour gérer les accès non autorisés
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

// Déclaration du contrôleur ForumController qui hérite du contrôleur abstrait de Symfony
class ForumController extends AbstractController
{
    // Déclaration des propriétés privées pour le gestionnaire d'entités, le paginator, et la sécurité
    private EntityManagerInterface $entityManager;
    private PaginatorInterface $paginator;
    private Security $security;

    // Constructeur pour injecter les services nécessaires
    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Security $security)
    {
        $this->entityManager = $entityManager; // Initialisation du gestionnaire d'entités
        $this->paginator = $paginator; // Initialisation du paginator
        $this->security = $security; // Initialisation du service de sécurité
    }

    // Route pour afficher la page d'accueil du forum
    #[Route('/forum', name: 'app_forum', methods: ['GET'])]
    public function index(CardRepository $cardRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll(); // Récupération de toutes les catégories
        $cardIds = [34, 35, 36]; // IDs des cartes à récupérer
        $cards = $cardRepository->findBy(['id' => $cardIds]); // Récupération des cartes correspondantes

        // Rendu de la vue 'forum/index.html.twig' avec les catégories et cartes récupérées
        return $this->render('forum/index.html.twig', [
            'categories' => $categories,
            'cards' => $cards,
        ]);
    }

    // Route pour afficher une catégorie spécifique du forum
    #[Route('/forum/category/{id}', name: 'forum_category_show', methods: ['GET', 'POST'])]
    public function showCategory(Request $request, Category $category, PaginatorInterface $paginator): Response
    {
        $currentUser = $this->getUser(); // Récupération de l'utilisateur courant
        $topics = $category->getTopics(); // Récupération des sujets associés à la catégorie
    
        if ($currentUser) { // Si l'utilisateur est connecté
            // Récupération des utilisateurs bloqués pour l'utilisateur courant
            $blockedUsers = $this->entityManager->getRepository(User::class)->findBlockedUsers($currentUser);
    
            // Filtrage des sujets pour exclure ceux créés par des utilisateurs bloqués
            $visibleTopics = array_filter($topics->toArray(), function (Topic $topic) use ($blockedUsers) {
                foreach ($blockedUsers as $blockedUser) {
                    if ($topic->getUser() === $blockedUser) {
                        return false; // Exclure le sujet si l'utilisateur est bloqué
                    }
                }
                return true; // Garder le sujet sinon
            });
    
            // Tri des sujets par date de création décroissante
            usort($visibleTopics, function (Topic $a, Topic $b) {
                return $b->getCreationDate() <=> $a->getCreationDate();
            });
    
            // Pagination des sujets filtrés à l'aide de KnpPaginator
            $pagination = $paginator->paginate(
                $visibleTopics, // Sujets filtrés
                $request->query->getInt('page', 1), // Numéro de la page actuelle
                10 // Nombre de sujets par page
            );
    
            // Création d'un nouveau sujet
            $topic = new Topic();
            $topic->setCategory($category); // Associe le sujet à la catégorie courante
            $topic->setUser($currentUser); // Associe le sujet à l'utilisateur courant
            $topic->setCreationDate(new \DateTime()); // Définit la date de création du sujet
    
            // Création du formulaire pour le nouveau sujet
            $form = $this->createForm(TopicType::class, $topic);
            $form->handleRequest($request); // Traite la requête
    
            if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
                $this->entityManager->persist($topic); // Persiste le sujet en base de données
                $this->entityManager->flush(); // Sauvegarde les modifications
    
                // Redirige vers la page d'affichage du sujet
                return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
            }
    
            // Rendu de la vue 'forum/category_show.html.twig' avec les données nécessaires
            return $this->render('forum/category_show.html.twig', [
                'category' => $category,
                'topics' => $pagination, // Passe la pagination à la vue
                'form' => $form->createView(), // Passe le formulaire à la vue
                'visibleTopics' => $visibleTopics, // Passe également les sujets visibles si besoin
            ]);
        } else {
            // Redirection vers la page de login si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
    }

    // Route pour créer un nouveau sujet dans une catégorie spécifique
    #[Route('/forum/category/{categoryId}/new-topic', name: 'forum_new_topic', methods: ['GET', 'POST'])]
    public function newTopic(Request $request, int $categoryId): Response
    {
        // Récupération de la catégorie à partir de l'ID
        $category = $this->entityManager->getRepository(Category::class)->find($categoryId);
        $topic = new Topic(); // Création d'une nouvelle instance de Topic

        if ($this->getUser()) { // Si l'utilisateur est connecté
            $topic->setCategory($category); // Associe la catégorie au sujet
            $topic->setUser($this->getUser()); // Associe l'utilisateur courant au sujet
            $topic->setCreationDate(new \DateTime()); // Définit la date de création du sujet

            // Création du formulaire pour le nouveau sujet
            $form = $this->createForm(TopicType::class, $topic);
            $form->handleRequest($request); // Traite la requête

            if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
                $this->entityManager->persist($topic); // Persiste le sujet en base de données
                $this->entityManager->flush(); // Sauvegarde les modifications

                // Redirige vers la page d'affichage du sujet
                return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
            }

            // Rendu de la vue 'forum/new_topic.html.twig' avec les données nécessaires
            return $this->render('forum/new_topic.html.twig', [
                'category' => $category,
                'form' => $form->createView(), // Passe le formulaire à la vue
            ]);
        } else {
            // Redirection vers la page de login si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
    }

    // Route pour afficher un sujet spécifique du forum
    #[Route('/forum/topic/{id}', name: 'forum_topic_show', methods: ['GET', 'POST'])]
    public function showTopic(Request $request, int $id, PostRepository $postRepository): Response
    {
        $topic = $this->entityManager->getRepository(Topic::class)->find($id); // Récupération du sujet par ID

        if (!$topic) { // Si le sujet n'existe pas
            throw $this->createNotFoundException('Topic not found'); // Lancer une exception de type "Not Found"
        }

        $category = $topic->getCategory(); // Récupération de la catégorie associée au sujet
        $currentUser = $this->security->getUser(); // Récupération de l'utilisateur courant
        $page = $request->query->getInt('page', 1); // Récupération du numéro de la page pour la pagination
        $posts = $postRepository->findByTopicPaginated($currentUser, $id, $page); // Récupération des posts paginés

        $post = new Post(); // Création d'une nouvelle instance de Post
        if ($currentUser) { // Si l'utilisateur est connecté
            $post->setTopic($topic); // Associe le post au sujet courant
            $post->setUser($currentUser); // Associe l'utilisateur courant au post

            // Création du formulaire pour le nouveau post
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request); // Traite la requête

            if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
                $this->entityManager->persist($post); // Persiste le post en base de données
                $this->entityManager->flush(); // Sauvegarde les modifications

                // Redirige vers la page d'affichage du sujet
                return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
            }

            // Rendu de la vue 'forum/topic_show.html.twig' avec les données nécessaires
            return $this->render('forum/topic_show.html.twig', [
                'topic' => $topic,
                'posts' => $posts, // Passe les posts paginés à la vue
                'form' => $form->createView(), // Passe le formulaire à la vue
                'category' => $category, // Passe la catégorie associée à la vue
            ]);
        } else {
            // Redirection vers la page de login si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
    }

    // Route pour éditer un sujet existant
    #[Route('/forum/topic/{id}/edit', name: 'forum_topic_edit', methods: ['GET', 'POST'])]
    public function editTopic(Request $request, Topic $topic): Response
    {
        $currentUser = $this->getUser(); // Récupération de l'utilisateur courant

        // Vérification des permissions : l'utilisateur courant doit être le créateur du sujet
        if (!$currentUser || $topic->getUser() !== $currentUser) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à éditer ce sujet.');
        }

        // Création du formulaire pour l'édition du sujet
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request); // Traite la requête

        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $this->entityManager->flush(); // Sauvegarde les modifications en base de données
            return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]); // Redirige vers la page d'affichage du sujet
        }

        // Rendu de la vue 'forum/edit_topic.html.twig' avec les données nécessaires
        return $this->render('forum/edit_topic.html.twig', [
            'topic' => $topic, // Passe le sujet à la vue
            'form' => $form->createView(), // Passe le formulaire à la vue
        ]);
    }

    // Route pour signaler un post
    #[Route('/forum/post/{id}/report', name: 'forum_post_report', methods: ['GET', 'POST'])]
    public function reportPost(Request $request, Post $post): Response
    {
        if (!$this->getUser()) { // Si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login'); // Redirection vers la page de login
        }

        $report = new UserReport(); // Création d'une nouvelle instance de UserReport
        $report->setPost($post); // Associe le post signalé au rapport
        $report->setReportingUser($this->getUser()); // Associe l'utilisateur courant au rapport
        $report->setReportedUser($post->getUser()); // Associe l'utilisateur qui a créé le post au rapport
        $report->setPostContent($post->getMessage());  // Sauvegarde le contenu du post dans le rapport

        // Création du formulaire pour le rapport
        $form = $this->createForm(UserReportType::class, $report);
        $form->handleRequest($request); // Traite la requête

        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $this->entityManager->persist($report); // Persiste le rapport en base de données
            $this->entityManager->flush(); // Sauvegarde les modifications

            // Redirige vers la page d'affichage du sujet contenant le post signalé
            return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]);
        }

        // Rendu de la vue 'forum/report_post.html.twig' avec les données nécessaires
        return $this->render('forum/report_post.html.twig', [
            'post' => $post, // Passe le post à la vue
            'form' => $form->createView(), // Passe le formulaire à la vue
        ]);
    }

    // Route pour éditer un post existant
    #[Route('/forum/post/{id}/edit', name: 'forum_post_edit', methods: ['GET', 'POST'])]
    public function editPost(Request $request, Post $post): Response
    {
        // Vérification des permissions : l'utilisateur courant doit être le créateur du post
        if (!$this->getUser() || $post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('You are not allowed to edit this post.');
        }

        // Création du formulaire pour l'édition du post
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request); // Traite la requête

        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $this->entityManager->flush(); // Sauvegarde les modifications en base de données
            return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]); // Redirige vers la page d'affichage du sujet
        }

        // Rendu de la vue 'forum/edit_post.html.twig' avec les données nécessaires
        return $this->render('forum/edit_post.html.twig', [
            'post' => $post, // Passe le post à la vue
            'form' => $form->createView(), // Passe le formulaire à la vue
        ]);
    }

    // Route pour supprimer un post existant
    #[Route('/forum/post/{id}/delete', name: 'forum_post_delete', methods: ['POST'])]
    public function deletePost(Request $request, Post $post): Response
    {
        // Vérification des permissions : l'utilisateur courant doit être le créateur du post
        if (!$this->getUser() || $post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('You are not allowed to delete this post.');
        }

        // Vérification de la validité du jeton CSRF
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($post); // Suppression du post de la base de données
            $this->entityManager->flush(); // Sauvegarde les modifications
        }

        // Redirige vers la page d'affichage du sujet contenant le post supprimé
        return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]);
    }
}
