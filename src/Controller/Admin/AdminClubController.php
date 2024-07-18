<?php
// src/Controller/AdminClubController.php

namespace App\Controller\Admin;

use App\Entity\Club;
use App\Form\ClubType;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/club')]
class AdminClubController extends AbstractController
{
    #[Route('/', name: 'app_admin_club_index', methods: ['GET'])]
    public function index(ClubRepository $clubRepository): Response
    {
        return $this->render('admin/admin_club/index.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_club_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $club = new Club();
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                // Déplace le fichier vers le répertoire public/media
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/media',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si nécessaire
                }

                // Met à jour le chemin d'accès au logo dans l'entité Club
                $club->setLogoClub('media/'.$newFilename);
            }

            $entityManager->persist($club);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_club/new.html.twig', [
            'club' => $club,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_club_show', methods: ['GET'])]
    public function show(Club $club): Response
    {
        return $this->render('admin/admin_club/show.html.twig', [
            'club' => $club,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_club_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                // Déplace le fichier vers le répertoire public/media
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/media',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si nécessaire
                }

                // Met à jour le chemin d'accès au logo dans l'entité Club
                $club->setLogoClub('media/'.$newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/admin_club/edit.html.twig', [
            'club' => $club,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_club_delete', methods: ['POST'])]
    public function delete(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
            $entityManager->remove($club);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_club_index', [], Response::HTTP_SEE_OTHER);
    }
}
