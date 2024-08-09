<?php

namespace App\Controller\Admin;

use App\Entity\Topic;
use App\Form\TopicType;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/topic')]
class AdminTopicController extends AbstractController
{
    #[Route('/', name: 'app_admin_topic_index', methods: ['GET'])]
    public function index(Request $request, TopicRepository $topicRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $topicRepository->createQueryBuilder('t');

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle
            10 // Nombre d'éléments par page
        );

        return $this->render('admin/admin_topic/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
    #[Route('/new', name: 'app_admin_topic_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $topic = new Topic();
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($topic);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_topic_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_topic/new.html.twig', [
            'topic' => $topic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_topic_show', methods: ['GET'])]
    public function show(Topic $topic): Response
    {
        return $this->render('admin/admin_topic/show.html.twig', [
            'topic' => $topic,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_topic_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_topic_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_topic/edit.html.twig', [
            'topic' => $topic,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_topic_delete', methods: ['POST'])]
    public function delete(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$topic->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($topic);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_topic_index', [], Response::HTTP_SEE_OTHER);
    }
}
