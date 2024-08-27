<?php
// src/Controller/AdminClubController.php

namespace App\Controller\Admin; // Déclare le namespace pour ce contrôleur sous le dossier Admin.

use App\Entity\Club; // Importe l'entité Club.
use App\Form\ClubType; // Importe le formulaire associé à l'entité Club.
use App\Repository\ClubRepository; // Importe le repository pour l'entité Club.
use Doctrine\ORM\EntityManagerInterface; // Importe l'interface pour la gestion des entités avec Doctrine.
use Knp\Component\Pager\PaginatorInterface; // Importe l'interface du composant de pagination KnpPaginator.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importe le contrôleur de base Symfony.
use Symfony\Component\HttpFoundation\Request; // Importe l'objet Request pour gérer les requêtes HTTP.
use Symfony\Component\HttpFoundation\Response; // Importe l'objet Response pour gérer les réponses HTTP.
use Symfony\Component\Routing\Annotation\Route; // Importe l'annotation Route pour définir les routes.
use Symfony\Component\HttpFoundation\File\Exception\FileException; // Importe l'exception pour gérer les erreurs liées aux fichiers.

#[Route('/admin/club')] // Définit le préfixe de route pour ce contrôleur.
class AdminClubController extends AbstractController // Déclare la classe AdminClubController qui hérite d'AbstractController.
{
    #[Route('/', name: 'app_admin_club_index', methods: ['GET'])] // Définit la route pour la page d'index des clubs, accessible via GET.
    public function index(Request $request, ClubRepository $clubRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $clubRepository->createQueryBuilder('c'); // Crée un QueryBuilder pour récupérer les clubs depuis la base de données.

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données à paginer.
            $request->query->getInt('page', 1), // Le numéro de la page actuelle, par défaut 1.
            10 // Nombre d'éléments par page.
        );

        return $this->render('admin/admin_club/index.html.twig', [ // Retourne la vue avec la pagination.
            'pagination' => $pagination,
        ]);
    }    

    #[Route('/new', name: 'app_admin_club_new', methods: ['GET', 'POST'])] // Définit la route pour créer un nouveau club, accessible via GET et POST.
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $club = new Club(); // Crée une nouvelle instance de l'entité Club.
        $form = $this->createForm(ClubType::class, $club); // Crée un formulaire pour l'entité Club.
        $form->handleRequest($request); // Gère la requête et associe les données du formulaire à l'entité.

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide.
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData(); // Récupère le fichier du logo depuis le formulaire.

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME); // Extrait le nom de fichier original.
                $newFilename = $originalFilename.'-'.uniqid().'.'.$logoFile->guessExtension(); // Génère un nouveau nom de fichier unique.

                // Déplace le fichier vers le répertoire public/media
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/media', // Spécifie le chemin de destination.
                        $newFilename // Utilise le nouveau nom de fichier.
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si nécessaire (ex. loguer l'erreur ou afficher un message utilisateur).
                }

                // Met à jour le chemin d'accès au logo dans l'entité Club
                $club->setLogoClub('media/'.$newFilename); // Enregistre le chemin du logo dans l'entité Club.
            }

            $entityManager->persist($club); // Persiste le nouveau club dans la base de données.
            $entityManager->flush(); // Exécute les changements dans la base de données.

            return $this->redirectToRoute('app_admin_club_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des clubs.
        }

        return $this->render('admin/admin_club/new.html.twig', [ // Retourne la vue du formulaire pour créer un nouveau club.
            'club' => $club,
            'form' => $form->createView(), // Passe la vue du formulaire au template.
        ]);
    }

    #[Route('/{id}', name: 'app_admin_club_show', methods: ['GET'])] // Définit la route pour afficher un club spécifique, accessible via GET.
    public function show(Club $club): Response
    {
        return $this->render('admin/admin_club/show.html.twig', [ // Retourne la vue pour afficher les détails du club.
            'club' => $club,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_club_edit', methods: ['GET', 'POST'])] // Définit la route pour éditer un club, accessible via GET et POST.
    public function edit(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClubType::class, $club); // Crée un formulaire pour l'entité Club à éditer.
        $form->handleRequest($request); // Gère la requête et associe les données du formulaire à l'entité.

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide.
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData(); // Récupère le fichier du logo depuis le formulaire.

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME); // Extrait le nom de fichier original.
                $newFilename = $originalFilename.'-'.uniqid().'.'.$logoFile->guessExtension(); // Génère un nouveau nom de fichier unique.

                // Déplace le fichier vers le répertoire public/media
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/media', // Spécifie le chemin de destination.
                        $newFilename // Utilise le nouveau nom de fichier.
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si nécessaire (ex. loguer l'erreur ou afficher un message utilisateur).
                }

                // Met à jour le chemin d'accès au logo dans l'entité Club
                $club->setLogoClub('media/'.$newFilename); // Enregistre le chemin du logo dans l'entité Club.
            }

            $entityManager->flush(); // Exécute les changements dans la base de données.

            return $this->redirectToRoute('app_admin_club_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des clubs.
        }

        return $this->render('admin/admin_club/edit.html.twig', [ // Retourne la vue du formulaire pour éditer le club.
            'club' => $club,
            'form' => $form->createView(), // Passe la vue du formulaire au template.
        ]);
    }

    #[Route('/{id}', name: 'app_admin_club_delete', methods: ['POST'])] // Définit la route pour supprimer un club, accessible via POST.
    public function delete(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) { // Vérifie la validité du token CSRF.
            $entityManager->remove($club); // Supprime le club de la base de données.
            $entityManager->flush(); // Exécute les changements dans la base de données.
        }

        return $this->redirectToRoute('app_admin_club_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des clubs.
    }
}
