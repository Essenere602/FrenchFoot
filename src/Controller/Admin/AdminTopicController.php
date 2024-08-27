<?php
// Déclaration du namespace pour organiser le code. Ici, il est utilisé pour les contrôleurs admin dans l'application.
namespace App\Controller\Admin;

// Importation des classes nécessaires
use App\Entity\Topic; // Importation de l'entité Topic pour les sujets
use App\Form\TopicType; // Importation du formulaire TopicType pour la gestion des sujets
use App\Repository\TopicRepository; // Importation du repository pour l'entité Topic
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface EntityManager pour les opérations sur la base de données
use Knp\Component\Pager\PaginatorInterface; // Importation de l'interface de pagination
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour gérer les réponses HTTP
use Symfony\Component\Routing\Attribute\Route; // Importation de l'attribut Route pour définir les routes

// Définition de la route de base pour ce contrôleur. Les méthodes de ce contrôleur seront accessibles sous '/admin/topic'.
#[Route('/admin/topic')]
class AdminTopicController extends AbstractController
{
    // Route pour afficher la liste des sujets, accessible via une requête GET
    #[Route('/', name: 'app_admin_topic_index', methods: ['GET'])]
    public function index(Request $request, TopicRepository $topicRepository, PaginatorInterface $paginator): Response
    {
        // Création d'un QueryBuilder pour l'entité Topic, pour construire la requête de sélection
        $queryBuilder = $topicRepository->createQueryBuilder('t');

        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle, par défaut 1
            10 // Nombre d'éléments par page
        );

        // Rendu du template Twig avec les résultats paginés
        return $this->render('admin/admin_topic/index.html.twig', [
            'pagination' => $pagination, // Passage de l'objet pagination au template
        ]);
    }

    // Route pour créer un nouveau sujet, accessible via GET (affichage du formulaire) et POST (soumission du formulaire)
    #[Route('/new', name: 'app_admin_topic_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $topic = new Topic(); // Création d'une nouvelle instance de Topic
        $form = $this->createForm(TopicType::class, $topic); // Création du formulaire basé sur le type TopicType
        $form->handleRequest($request); // Gestion de la requête HTTP avec le formulaire

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($topic); // Préparation de l'entité pour la persistance
            $entityManager->flush(); // Enregistrement dans la base de données

            // Redirection vers la liste des sujets après la création
            return $this->redirectToRoute('app_admin_topic_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig pour le formulaire de création
        return $this->render('admin/admin_topic/new.html.twig', [
            'topic' => $topic, // Passage de l'entité topic au template
            'form' => $form, // Passage du formulaire au template
        ]);
    }

    // Route pour afficher un sujet spécifique, accessible via une requête GET
    #[Route('/{id}', name: 'app_admin_topic_show', methods: ['GET'])]
    public function show(Topic $topic): Response
    {
        // Rendu du template Twig pour afficher les détails du sujet
        return $this->render('admin/admin_topic/show.html.twig', [
            'topic' => $topic, // Passage de l'entité topic au template
        ]);
    }

    // Route pour éditer un sujet spécifique, accessible via GET (affichage du formulaire) et POST (soumission du formulaire)
    #[Route('/{id}/edit', name: 'app_admin_topic_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TopicType::class, $topic); // Création du formulaire pour l'entité Topic existante
        $form->handleRequest($request); // Gestion de la requête HTTP avec le formulaire

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // Enregistrement des modifications dans la base de données

            // Redirection vers la liste des sujets après la mise à jour
            return $this->redirectToRoute('app_admin_topic_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig pour le formulaire d'édition
        return $this->render('admin/admin_topic/edit.html.twig', [
            'topic' => $topic, // Passage de l'entité topic au template
            'form' => $form, // Passage du formulaire au template
        ]);
    }

    // Route pour supprimer un sujet spécifique, accessible uniquement via une requête POST
    #[Route('/{id}', name: 'app_admin_topic_delete', methods: ['POST'])]
    public function delete(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        // Vérification du jeton CSRF pour la suppression
        if ($this->isCsrfTokenValid('delete'.$topic->getId(), $request->request->get('_token'))) {
            $entityManager->remove($topic); // Suppression de l'entité de la base de données
            $entityManager->flush(); // Enregistrement des modifications dans la base de données
        }

        // Redirection vers la liste des sujets après la suppression
        return $this->redirectToRoute('app_admin_topic_index', [], Response::HTTP_SEE_OTHER);
    }
}
