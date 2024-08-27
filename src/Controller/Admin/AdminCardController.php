<?php

namespace App\Controller\Admin; // Déclare l'espace de noms du contrôleur Admin

use App\Entity\Card; // Importe l'entité Card
use App\Form\CardType; // Importe le formulaire CardType
use App\Repository\CardRepository; // Importe le repository CardRepository
use Knp\Component\Pager\PaginatorInterface; // Importe l'interface Paginator pour la pagination
use Symfony\Component\HttpFoundation\File\Exception\FileException; // Importe l'exception FileException pour gérer les erreurs de fichier
use Doctrine\ORM\EntityManagerInterface; // Importe l'interface EntityManager pour la gestion des entités
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importe le contrôleur de base de Symfony
use Symfony\Component\HttpFoundation\Request; // Importe la classe Request de Symfony
use Symfony\Component\HttpFoundation\Response; // Importe la classe Response de Symfony
use Symfony\Component\String\Slugger\SluggerInterface; // Importe l'interface SluggerInterface pour générer des slugs sécurisés
use Symfony\Component\Routing\Attribute\Route; // Importe l'attribut Route pour définir les routes

#[Route('/admin/card')] // Définit la route de base pour ce contrôleur
class AdminCardController extends AbstractController // Déclare la classe AdminCardController qui hérite d'AbstractController
{
    #[Route('/', name: 'app_admin_card_index', methods: ['GET'])] // Définit la route pour la liste des cartes
    public function index(Request $request, CardRepository $cardRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $cardRepository->createQueryBuilder('c'); // Crée un QueryBuilder pour récupérer les cartes avec l'alias 'c'

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données à paginer
            $request->query->getInt('page', 1), // Récupère le numéro de la page actuelle, par défaut 1
            10 // Nombre d'éléments par page
        );

        return $this->render('admin/admin_card/index.html.twig', [ // Rend le template Twig avec les données
            'pagination' => $pagination, // Passe l'objet de pagination à la vue
        ]);
    }

    #[Route('/new', name: 'app_admin_card_new', methods: ['GET', 'POST'])] // Définit la route pour créer une nouvelle carte
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $card = new Card(); // Crée une nouvelle instance de Card
        $form = $this->createForm(CardType::class, $card); // Crée un formulaire basé sur CardType et lié à l'entité Card
        $form->handleRequest($request); // Traite la requête du formulaire

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide
            $imageFile = $form->get('image')->getData(); // Récupère le fichier image téléchargé depuis le formulaire

            if ($imageFile) { // Vérifie si un fichier image a été téléchargé
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME); // Obtient le nom original du fichier sans l'extension
                $safeFilename = $slugger->slug($originalFilename); // Génère un slug sécurisé à partir du nom original
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension(); // Crée un nouveau nom de fichier unique

                try {
                    $imageFile->move(
                        $this->getParameter('media_directory'), // Récupère le répertoire de destination depuis les paramètres
                        $newFilename // Le nouveau nom de fichier
                    );
                } catch (FileException $e) { // Capture les exceptions lors du déplacement du fichier
                    // Gère l'exception si une erreur survient pendant l'upload du fichier
                }

                $card->setImage('media/' . $newFilename); // Définit le chemin de l'image dans l'entité Card
            }

            $entityManager->persist($card); // Prépare l'entité Card pour la persistance en base de données
            $entityManager->flush(); // Exécute la persistance en base de données

            return $this->redirectToRoute('app_admin_card_index'); // Redirige vers la liste des cartes
        }

        return $this->render('admin/admin_card/new.html.twig', [ // Rend le template Twig du formulaire de création
            'card' => $card, // Passe l'entité Card à la vue
            'form' => $form->createView(), // Passe la vue du formulaire à la vue
        ]);
    }

    #[Route('/{id}', name: 'app_admin_card_show', methods: ['GET'])] // Définit la route pour afficher une carte spécifique
    public function show(Card $card): Response
    {
        return $this->render('admin/admin_card/show.html.twig', [ // Rend le template Twig pour afficher la carte
            'card' => $card, // Passe l'entité Card à la vue
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_card_edit', methods: ['GET', 'POST'])] // Définit la route pour éditer une carte
    public function edit(Request $request, Card $card, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CardType::class, $card); // Crée un formulaire basé sur CardType et lié à l'entité Card
        $form->handleRequest($request); // Traite la requête du formulaire
    
        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide
            $imageFile = $form->get('image')->getData(); // Récupère le fichier image téléchargé depuis le formulaire
    
            if ($imageFile) { // Vérifie si un fichier image a été téléchargé
                // Supprimer l'ancienne image
                $existingImage = $card->getImage(); // Obtient le chemin de l'image existante
                if ($existingImage) { // Vérifie si une image existante est définie
                    $oldImagePath = $this->getParameter('media_directory') . '/' . basename($existingImage); // Définit le chemin complet de l'ancienne image
                    if (file_exists($oldImagePath)) { // Vérifie si le fichier existe
                        unlink($oldImagePath); // Supprime l'ancienne image du serveur
                    }
                }
    
                // Processus de traitement et de téléchargement de la nouvelle image
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME); // Obtient le nom original du fichier sans l'extension
                $safeFilename = $slugger->slug($originalFilename); // Génère un slug sécurisé à partir du nom original
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension(); // Crée un nouveau nom de fichier unique
    
                try {
                    $imageFile->move(
                        $this->getParameter('media_directory'), // Récupère le répertoire de destination depuis les paramètres
                        $newFilename // Le nouveau nom de fichier
                    );
                } catch (FileException $e) { // Capture les exceptions lors du déplacement du fichier
                    // Gère l'exception si une erreur survient pendant l'upload du fichier
                }
    
                $card->setImage('media/' . $newFilename); // Définit le chemin de la nouvelle image dans l'entité Card
            }
    
            $entityManager->flush(); // Exécute la mise à jour en base de données
    
            return $this->redirectToRoute('app_admin_card_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la liste des cartes avec un code de statut HTTP approprié
        }
    
        return $this->render('admin/admin_card/edit.html.twig', [ // Rend le template Twig du formulaire d'édition
            'card' => $card, // Passe l'entité Card à la vue
            'form' => $form, // Passe le formulaire à la vue
        ]);
    }
    
    #[Route('/{id}', name: 'app_admin_card_delete', methods: ['POST'])] // Définit la route pour supprimer une carte
    public function delete(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$card->getId(), $request->getPayload()->getString('_token'))) { // Vérifie la validité du token CSRF
            $entityManager->remove($card); // Supprime l'entité Card de la base de données
            $entityManager->flush(); // Exécute la suppression en base de données
        }

        return $this->redirectToRoute('app_admin_card_index', [], Response::HTTP_SEE_OTHER); // Redirige vers la liste des cartes avec un code de statut HTTP approprié
    }
}
