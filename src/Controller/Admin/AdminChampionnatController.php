<?php

namespace App\Controller\Admin;

use App\Entity\Championnat;
use App\Form\ChampionnatType;
use App\Repository\ChampionnatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/championnat')]
class AdminChampionnatController extends AbstractController
{
    #[Route('/', name: 'app_admin_championnat_index', methods: ['GET'])]
    public function index(Request $request, ChampionnatRepository $championnatRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $championnatRepository->createQueryBuilder('c');

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle
            10 // Nombre d'éléments par page
        );

        return $this->render('admin/admin_championnat/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
    #[Route('/new', name: 'app_admin_championnat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $championnat = new Championnat();
        $form = $this->createForm(ChampionnatType::class, $championnat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($championnat);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_championnat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_championnat/new.html.twig', [
            'championnat' => $championnat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_championnat_show', methods: ['GET'])]
    public function show(Championnat $championnat): Response
    {
        return $this->render('admin/admin_championnat/show.html.twig', [
            'championnat' => $championnat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_championnat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Championnat $championnat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChampionnatType::class, $championnat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_championnat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_championnat/edit.html.twig', [
            'championnat' => $championnat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_championnat_delete', methods: ['POST'])]
    public function delete(Request $request, Championnat $championnat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$championnat->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($championnat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_championnat_index', [], Response::HTTP_SEE_OTHER);
    }
}
