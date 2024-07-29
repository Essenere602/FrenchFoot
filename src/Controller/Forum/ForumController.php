<?php

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
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class ForumController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PaginatorInterface $paginator;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    #[Route('/forum', name: 'app_forum', methods: ['GET'])]
    public function index(CardRepository $cardRepository, CategoryRepository $categoryRepository): Response
    {
        // Récupérer toutes les catégories
        $categories = $categoryRepository->findAll();
        
        // Récupérer uniquement les cartes avec les IDs 34, 35, 36
        $cardIds = [34, 35, 36];
        $cards = $cardRepository->findBy(['id' => $cardIds]);

        return $this->render('forum/index.html.twig', [
            'categories' => $categories,
            'cards' => $cards,
        ]);
    }

    #[Route('/forum/category/{id}', name: 'forum_category_show', methods: ['GET', 'POST'])]
    public function showCategory(Request $request, Category $category): Response
    {
        $topics = $category->getTopics();

        $topic = new Topic();
        $topic->setCategory($category);
        $topic->setUser($this->getUser()); // Assuming user is authenticated
        $topic->setCreationDate(new \DateTime()); // Set the creation date

        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($topic);
            $this->entityManager->flush();

            return $this->redirectToRoute('forum_topic_show', ['id' => $topic->getId()]);
        }

        return $this->render('forum/category_show.html.twig', [
            'category' => $category,
            'topics' => $topics,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/category/{categoryId}/new-topic', name: 'forum_new_topic', methods: ['GET', 'POST'])]
    public function newTopic(Request $request, int $categoryId): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($categoryId);
        $topic = new Topic();
        $topic->setCategory($category);
        $topic->setUser($this->getUser()); // Assuming user is authenticated
        $topic->setCreationDate(new \DateTime()); // Set the creation date

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
    }

    #[Route('/forum/topic/{id}', name: 'forum_topic_show', methods: ['GET', 'POST'])]
    public function showTopic(Request $request, int $id): Response
    {
        $topic = $this->entityManager->getRepository(Topic::class)->find($id);

        if (!$topic) {
            throw $this->createNotFoundException('Topic not found');
        }

        $page = $request->query->getInt('page', 1);
        $posts = $this->entityManager->getRepository(Post::class)->findByTopicPaginated($id, $page);

        $post = new Post();
        $post->setTopic($topic);
        $post->setUser($this->getUser()); // Assuming user is authenticated

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
    }

    #[Route('/forum/post/{id}/report', name: 'forum_post_report', methods: ['GET', 'POST'])]
    public function reportPost(Request $request, Post $post): Response
    {
        $report = new UserReport();
        $report->setPost($post);
        $report->setReportingUser($this->getUser()); // Assuming user is authenticated
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
    // Vérifiez si l'utilisateur connecté est l'auteur du post
    if ($post->getUser() !== $this->getUser()) {
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
    // Vérifiez si l'utilisateur connecté est l'auteur du post
    if ($post->getUser() !== $this->getUser()) {
        throw new AccessDeniedException('You are not allowed to delete this post.');
    }

    // Vérifiez le token CSRF
    if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    return $this->redirectToRoute('forum_topic_show', ['id' => $post->getTopic()->getId()]);
}
    
}
