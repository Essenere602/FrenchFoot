<?php
// Déclaration du namespace pour organiser le code. Ici, il est utilisé pour les contrôleurs admin dans l'application.
namespace App\Controller\Admin;

// Importation des classes nécessaires
use App\Entity\UserBanned; // Importation de l'entité UserBanned pour gérer les utilisateurs bannis
use App\Entity\UserReport; // Importation de l'entité UserReport pour les rapports d'utilisateur
use App\Form\UserReportType; // Importation du formulaire UserReportType
use Knp\Component\Pager\PaginatorInterface; // Importation de l'interface de pagination
use App\Repository\UserReportRepository; // Importation du repository pour l'entité UserReport
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface EntityManager pour les opérations sur la base de données
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour gérer les réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Importation de l'annotation Route pour définir les routes

// Définition de la route pour ce contrôleur. Les méthodes de ce contrôleur seront accessibles sous '/admin/report'.
#[Route('/admin/report')]
class AdminReportController extends AbstractController
{
    // Route pour afficher la liste des rapports, accessible via une requête GET
    #[Route('/', name: 'app_admin_report_index', methods: ['GET'])]
    public function index(Request $request, UserReportRepository $userReportRepository, PaginatorInterface $paginator): Response
    {
        // Création d'un QueryBuilder pour l'entité UserReport, pour construire la requête de sélection
        $queryBuilder = $userReportRepository->createQueryBuilder('ur')
            ->where('ur.archived = false'); // Assurer que nous ne paginons que les rapports non archivés

        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle, par défaut 1
            10 // Nombre d'éléments par page
        );

        // Rendu du template Twig avec les résultats paginés
        return $this->render('admin/admin_report/index.html.twig', [
            'pagination' => $pagination, // Passage de l'objet pagination au template
        ]);
    }

    // Route pour créer un nouveau rapport, accessible via GET (affichage du formulaire) et POST (soumission du formulaire)
    #[Route('/new', name: 'app_admin_report_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userReport = new UserReport(); // Création d'une nouvelle instance de UserReport
        $form = $this->createForm(UserReportType::class, $userReport); // Création du formulaire basé sur le type UserReportType
        $form->handleRequest($request); // Gestion de la requête HTTP avec le formulaire

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userReport); // Préparation de l'entité pour la persistance
            $entityManager->flush(); // Enregistrement dans la base de données

            // Redirection vers la liste des rapports après la création
            return $this->redirectToRoute('app_admin_report_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig pour le formulaire de création
        return $this->render('admin/admin_report/new.html.twig', [
            'user_report' => $userReport, // Passage de l'entité userReport au template
            'form' => $form, // Passage du formulaire au template
        ]);
    }

    // Route pour afficher les rapports archivés, accessible via une requête GET
    #[Route('/archived', name: 'app_admin_report_archived', methods: ['GET'])]
    public function archived(UserReportRepository $userReportRepository): Response
    {
        // Récupération de tous les rapports archivés
        $userReports = $userReportRepository->findAllArchivedReports();
        
        // Rendu du template Twig avec la liste des rapports archivés
        return $this->render('admin/admin_report/archived.html.twig', [
            'user_reports' => $userReports, // Passage de la liste des rapports archivés au template
        ]);
    }

    // Route pour afficher un rapport spécifique, accessible via une requête GET
    #[Route('/{id}', name: 'app_admin_report_show', methods: ['GET'])]
    public function show(UserReport $userReport): Response
    {
        // Rendu du template Twig pour afficher les détails du rapport
        return $this->render('admin/admin_report/show.html.twig', [
            'user_report' => $userReport, // Passage de l'entité userReport au template
        ]);
    }

    // Route pour éditer un rapport spécifique, accessible via GET (affichage du formulaire) et POST (soumission du formulaire)
    #[Route('/{id}/edit', name: 'app_admin_report_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserReportType::class, $userReport); // Création du formulaire pour l'entité UserReport existante
        $form->handleRequest($request); // Gestion de la requête HTTP avec le formulaire

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // Enregistrement des modifications dans la base de données

            // Redirection vers la liste des rapports après la mise à jour
            return $this->redirectToRoute('app_admin_report_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig pour le formulaire d'édition
        return $this->render('admin/admin_report/edit.html.twig', [
            'user_report' => $userReport, // Passage de l'entité userReport au template
            'form' => $form, // Passage du formulaire au template
        ]);
    }

    // Route pour supprimer un rapport spécifique, accessible uniquement via une requête POST
    #[Route('/{id}', name: 'app_admin_report_delete', methods: ['POST'])]
    public function delete(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        // Vérification du jeton CSRF pour la suppression
        if ($this->isCsrfTokenValid('delete'.$userReport->getId(), $request->request->get('_token'))) {
            $entityManager->remove($userReport); // Suppression de l'entité de la base de données
            $entityManager->flush(); // Enregistrement des modifications dans la base de données
        }

        // Redirection vers la liste des rapports après la suppression
        return $this->redirectToRoute('app_admin_report_index', [], Response::HTTP_SEE_OTHER);
    }

    // Route pour bannir un utilisateur en fonction d'un rapport, accessible uniquement via une requête POST
    #[Route('/{id}/ban', name: 'app_admin_report_ban', methods: ['POST'])]
    public function ban(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        $user = $userReport->getReportedUser(); // Récupération de l'utilisateur signalé
        
        // Vérification si l'utilisateur est déjà banni
        $existingBan = $entityManager->getRepository(UserBanned::class)->findOneBy(['user' => $user]);
        
        if (!$existingBan) {
            // Si l'utilisateur n'est pas encore banni, création d'un nouvel enregistrement de bannissement
            $userBanned = new UserBanned();
            $userBanned->setUser($user);
            $userBanned->setBannedDate(new \DateTime()); // Date du bannissement
            $userBanned->setNumberBan(1); // Premier bannissement
            $userBanned->setPermanentlyBanned(false); // Pas encore banni de manière permanente
    
            $entityManager->persist($userBanned); // Préparation de l'entité pour la persistance
        } else {
            // Si l'utilisateur est déjà banni, mise à jour du nombre de bannissements et de la date
            $existingBan->setNumberBan($existingBan->getNumberBan() + 1);
            $existingBan->setBannedDate(new \DateTime());
    
            // Mise à jour du statut de bannissement permanent
            if ($existingBan->getNumberBan() >= 3) {
                $existingBan->setPermanentlyBanned(true);
            }
    
            $entityManager->persist($existingBan); // Préparation de l'entité pour la persistance
        }
    
        $entityManager->flush(); // Enregistrement des modifications dans la base de données
    
        // Redirection vers la liste des rapports après le bannissement
        return $this->redirectToRoute('app_admin_report_index');
    }

    // Route pour archiver un rapport, accessible uniquement via une requête POST
    #[Route('/{id}/archive', name: 'app_admin_report_archive', methods: ['POST'])]
    public function archive(Request $request, UserReport $userReport, EntityManagerInterface $entityManager): Response
    {
        // Vérification du jeton CSRF pour l'archivage
        if ($this->isCsrfTokenValid('archive'.$userReport->getId(), $request->request->get('_token'))) {
            $userReport->setArchived(true); // Marquer le rapport comme archivé
            $entityManager->flush(); // Enregistrement des modifications dans la base de données
        }

        // Redirection vers la liste des rapports après l'archivage
        return $this->redirectToRoute('app_admin_report_index');
    }
}
