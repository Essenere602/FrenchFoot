<?php

namespace App\Controller\Admin; // Déclare le namespace pour ce contrôleur sous le dossier Admin.

use App\Entity\Championnat; // Importe l'entité Championnat.
use App\Form\ChampionnatType; // Importe le formulaire associé à l'entité Championnat.
use App\Repository\ChampionnatRepository; // Importe le repository pour l'entité Championnat.
use Doctrine\ORM\EntityManagerInterface; // Importe l'interface pour la gestion des entités avec Doctrine.
use Knp\Component\Pager\PaginatorInterface; // Importe l'interface du composant de pagination KnpPaginator.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importe le contrôleur de base Symfony.
use Symfony\Component\HttpFoundation\Request; // Importe l'objet Request pour gérer les requêtes HTTP.
use Symfony\Component\HttpFoundation\Response; // Importe l'objet Response pour gérer les réponses HTTP.
use Symfony\Component\Routing\Attribute\Route; // Importe l'attribut Route pour définir les routes.

#[Route('/admin/championnat')] // Définit le préfixe de route pour ce contrôleur.
class AdminChampionnatController extends AbstractController // Déclare la classe AdminChampionnatController qui hérite d'AbstractController.
{
    #[Route('/', name: 'app_admin_championnat_index', methods: ['GET'])] // Définit la route pour la page d'index des championnats, accessible via GET.
    public function index(Request $request, ChampionnatRepository $championnatRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $championnatRepository->createQueryBuilder('c'); // Crée un QueryBuilder pour récupérer les championnats depuis la base de données.

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données à paginer.
            $request->query->getInt('page', 1), // Le numéro de la page actuelle, par défaut 1.
            10 // Nombre d'éléments par page.
        );

        return $this->render('admin/admin_championnat/index.html.twig', [ // Retourne la vue avec la pagination.
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_championnat_new', methods: ['GET', 'POST'])] // Définit la route pour créer un nouveau championnat, accessible via GET et POST.
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $championnat = new Championnat(); // Crée une nouvelle instance de l'entité Championnat.
        $form = $this->createForm(ChampionnatType::class, $championnat); // Crée un formulaire pour l'entité Championnat.
        $form->handleRequest($request); // Gère la requête et associe les données du formulaire à l'entité.

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide.
            $entityManager->persist($championnat); // Persiste le nouveau championnat dans la base de données.
            $entityManager->flush(); // Exécute les changements dans la base de données.

            return $this->redirectToRoute('app_admin_championnat_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des championnats.
        }

        return $this->render('admin/admin_championnat/new.html.twig', [ // Retourne la vue du formulaire pour créer un nouveau championnat.
            'championnat' => $championnat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_championnat_show', methods: ['GET'])] // Définit la route pour afficher un championnat spécifique, accessible via GET.
    public function show(Championnat $championnat): Response
    {
        return $this->render('admin/admin_championnat/show.html.twig', [ // Retourne la vue pour afficher les détails du championnat.
            'championnat' => $championnat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_championnat_edit', methods: ['GET', 'POST'])] // Définit la route pour éditer un championnat, accessible via GET et POST.
    public function edit(Request $request, Championnat $championnat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChampionnatType::class, $championnat); // Crée un formulaire pour l'entité Championnat à éditer.
        $form->handleRequest($request); // Gère la requête et associe les données du formulaire à l'entité.

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide.
            $entityManager->flush(); // Exécute les changements dans la base de données.

            return $this->redirectToRoute('app_admin_championnat_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des championnats.
        }

        return $this->render('admin/admin_championnat/edit.html.twig', [ // Retourne la vue du formulaire pour éditer le championnat.
            'championnat' => $championnat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_championnat_delete', methods: ['POST'])] // Définit la route pour supprimer un championnat, accessible via POST.
    public function delete(Request $request, Championnat $championnat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$championnat->getId(), $request->getPayload()->getString('_token'))) { // Vérifie la validité du token CSRF.
            $entityManager->remove($championnat); // Supprime le championnat de la base de données.
            $entityManager->flush(); // Exécute les changements dans la base de données.
        }

        return $this->redirectToRoute('app_admin_championnat_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la page d'index des championnats.
    }
}

