<?php

// src/Controller/Forum/ForumController.php

namespace App\Controller\Forum;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\UserReport;
use App\Form\PostType;
use App\Form\TopicType;
use App\Form\UserReportType;
use App\Repository\CardRepository;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ForumController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PaginatorInterface $paginator;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->security = $security;
    }

    #[Route('/forum', name: 'app_forum', methods: ['GET'])]
    public function index(CardRepository $cardRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $cardIds = [34, 35, 36];
        $cards = $cardRepository->findBy(['id' => $cardIds]);

        return $this->render('forum/index.html.twig', [
            'categories' => $categories,
            'cards' => $cards,
        ]);
    }

    #[Route('/forum/category/{id}', name: 'forum_category_show', methods: ['GET', 'POST'])]
    public function showCategory(Request $request, Category $category, PaginatorInterface $paginator): Response
    {
        $currentUser = $this->getUser();
        $topics = $category->getTopics();
    
        if ($currentUser) {
            // Récupérer les utilisateurs bloqués
            $blockedUsers = $this->entityManager->getRepository(User::class)->findBlockedUsers($currentUser);
    
            // Filtrer les sujets pour exclure ceux des utilisateurs bloqués
            $visibleTopics = array_filter($topics->toArray(), function (Topic $topic) use ($blockedUsers) {
                foreach ($blockedUsers as $blockedUser) {
                    if ($topic->getUser() === $blockedUser) {
                        return false;
                    }
                }
                return true;
            });
    
            // Trier les sujets par date de création décroissante
            usort($visibleTopics, function (Topic $a, Topic $b) {
                return $b->getCreationDate() <=> $a->getCreationDate();
            });
    
            // Utiliser KNP Paginator pour paginer les sujets filtrés
            $pagination = $paginator->paginate(
                $visibleTopics, // Les sujets filtrés
                $request->query->getInt('page', 1), // Le numéro de la page actuelle
                10 // Nombre de sujets par page
            );
    
            // Création d'un nouveau sujet
            $topic = new Topic();
            $topic->setCategory($category);
            $topic->setUser($currentUser);
            $topic->setCreationDate(new \DateTime());
    
            $form = $this->createForm(TopicType::class, $topic);
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($topic);
                $this->entityManager->flush();
    
                return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
            }
    
            return $this->render('forum/category_show.html.twig', [
                'category' => $category,
                'topics' => $pagination, // On passe ici la pagination plutôt que $visibleTopics
                'form' => $form->createView(),
                'visibleTopics' => $visibleTopics, // On passe également $visibleTopics si tu en as besoin ailleurs dans le template
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }
        #[Route('/forum/category/{categoryId}/new-topic', name: 'forum_new_topic', methods: ['GET', 'POST'])]
    public function newTopic(Request $request, int $categoryId): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($categoryId);
        $topic = new Topic();
        if ($this->getUser()) {
            $topic->setCategory($category);
            $topic->setUser($this->getUser());
            $topic->setCreationDate(new \DateTime());

            $form = $this->createForm(TopicType::class, $topic);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($topic);
                $this->entityManager->flush();

                return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
            }

            return $this->render('forum/new_topic.html.twig', [
                'category' => $category,
                'form' => $form->createView(),
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/forum/topic/{id}', name: 'forum_topic_show', methods: ['GET', 'POST'])]
    public function showTopic(Request $request, int $id, PostRepository $postRepository): Response
    {
        $topic = $this->entityManager->getRepository(Topic::class)->find($id);

        if (!$topic) {
            throw $this->createNotFoundException('Topic not found');
        }

        $currentUser = $this->security->getUser();
        $page = $request->query->getInt('page', 1);
        $posts = $postRepository->findByTopicPaginated($currentUser, $id, $page);

        $post = new Post();
        if ($currentUser) {
            $post->setTopic($topic);
            $post->setUser($currentUser);

            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($post);
                $this->entityManager->flush();

                return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
            }

            return $this->render('forum/topic_show.html.twig', [
                'topic' => $topic,
                'posts' => $posts,
                'form' => $form->createView(),
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }
    #[Route('/forum/topic/{id}/edit', name: 'forum_topic_edit', methods: ['GET', 'POST'])]
public function editTopic(Request $request, Topic $topic): Response
{
    $currentUser = $this->getUser();

    if (!$currentUser || $topic->getUser() !== $currentUser) {
        throw new AccessDeniedException('Vous n\'êtes pas autorisé à éditer ce sujet.');
    }

    $form = $this->createForm(TopicType::class, $topic);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->flush();
        return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
    }

    return $this->render('forum/edit_topic.html.twig', [
        'topic' => $topic,
        'form' => $form->createView(),
    ]);
}
    #[Route('/forum/post/{id}/report', name: 'forum_post_report', methods: ['GET', 'POST'])]
    public function reportPost(Request $request, Post $post): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $report = new UserReport();
        $report->setPost($post);
        $report->setReportingUser($this->getUser());
        $report->setReportedUser($post->getUser());

        $form = $this->createForm(UserReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($report);
            $this->entityManager->flush();

            return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]);
        }

        return $this->render('forum/report_post.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/post/{id}/edit', name: 'forum_post_edit', methods: ['GET', 'POST'])]
    public function editPost(Request $request, Post $post): Response
    {
        if (!$this->getUser() || $post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('You are not allowed to edit this post.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]);
        }

        return $this->render('forum/edit_post.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/post/{id}/delete', name: 'forum_post_delete', methods: ['POST'])]
    public function deletePost(Request $request, Post $post): Response
    {
        if (!$this->getUser() || $post->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('You are not allowed to delete this post.');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]);
    }
}
