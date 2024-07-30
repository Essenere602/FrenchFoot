<?php

namespace App\Controller\Admin;

use App\Entity\UserBanned;
use App\Entity\UserReport;
use App\Form\UserReportType;
use App\Repository\UserReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/report')]
class AdminReportController extends AbstractController
{
    #[Route('/', name: 'app_admin_report_index', methods: ['GET'])]
    public function index(UserReportRepository $userReportRepository): Response
    {
        return $this->render('admin/admin_report/index.html.twig', [
            'user_reports' => $userReportRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_report_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userReport = new UserReport();
        $form = $this->createForm(UserReportType::class, $userReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userReport);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_report_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_report/new.html.twig', [
            'user_report' => $userReport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_report_show', methods: ['GET'])]
    public function show(UserReport $userReport): Response
    {
        return $this->render('admin/admin_report/show.html.twig', [
            'user_report' => $userReport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_report_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserReportType::class, $userReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_report_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_report/edit.html.twig', [
            'user_report' => $userReport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_report_delete', methods: ['POST'])]
    public function delete(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userReport->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($userReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_report_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/ban', name: 'app_admin_report_ban', methods: ['POST'])]
    public function ban(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        $user = $userReport->getReportedUser();
        
        $existingBan = $entityManager->getRepository(UserBanned::class)->findOneBy(['user' => $user]);
        
        if (!$existingBan) {
            $userBanned = new UserBanned();
            $userBanned->setUser($user);
            $userBanned->setBannedDate(new \DateTime());
            $userBanned->setNumberBan(1); // Premier bannissement
            $userBanned->setPermanentlyBanned(false);
    
            $entityManager->persist($userBanned);
        } else {
            $existingBan->setNumberBan($existingBan->getNumberBan() + 1);
            $existingBan->setBannedDate(new \DateTime());
    
            // Met à jour le statut de bannissement permanent
            if ($existingBan->getNumberBan() >= 3) {
                $existingBan->setPermanentlyBanned(true);
            }
    
            $entityManager->persist($existingBan);
        }
    
        $entityManager->flush();
    
        return $this->redirectToRoute('app_admin_report_index');
    }
}
