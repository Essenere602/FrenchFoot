<?php

namespace App\Controller\Admin;

use App\Entity\UserBanned;
use App\Form\UserBannedType;
use App\Repository\UserBannedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/banned')]
class AdminBannedController extends AbstractController
{
    #[Route('/', name: 'app_admin_banned_index', methods: ['GET'])]
    public function index(UserBannedRepository $userBannedRepository): Response
    {
        return $this->render('admin_banned/index.html.twig', [
            'user_banneds' => $userBannedRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_banned_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userBanned = new UserBanned();
        $form = $this->createForm(UserBannedType::class, $userBanned);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userBanned);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_banned_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_banned/new.html.twig', [
            'user_banned' => $userBanned,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_banned_show', methods: ['GET'])]
    public function show(UserBanned $userBanned): Response
    {
        return $this->render('admin_banned/show.html.twig', [
            'user_banned' => $userBanned,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_banned_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserBanned $userBanned, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserBannedType::class, $userBanned);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_banned_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_banned/edit.html.twig', [
            'user_banned' => $userBanned,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_banned_delete', methods: ['POST'])]
    public function delete(Request $request, UserBanned $userBanned, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userBanned->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($userBanned);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_banned_index', [], Response::HTTP_SEE_OTHER);
    }
}
