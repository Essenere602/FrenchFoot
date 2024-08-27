<?php
// Déclaration du namespace pour organiser le code. Ici, il est utilisé pour les contrôleurs liés au football.
namespace App\Controller\Football;

// Importation des classes nécessaires
use App\Repository\CardRepository; // Importation du repository CardRepository pour accéder aux cartes
use App\Repository\ChampionnatRepository; // Importation du repository ChampionnatRepository pour accéder aux championnats
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour gérer les réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Importation de l'annotation Route pour définir les routes

// Définition du contrôleur pour les championnats
class ChampionnatController extends AbstractController
{
    // Route pour afficher la liste des championnats et des cartes, accessible via une requête GET
    #[Route('/championnats', name: 'championnat_index', methods: ['GET'])]
    public function index(ChampionnatRepository $championnatRepository, CardRepository $cardRepository): Response
    {
        // Récupérer tous les championnats depuis le repository
        $championnats = $championnatRepository->findAll();
        
        // Récupérer toutes les cartes depuis le repository
        $cards = $cardRepository->findAll();

        // Filtrer les cartes pour exclure celles avec les IDs spécifiques
        $excludedIds = [34, 35, 36]; // Liste des IDs de cartes à exclure
        $filteredCards = array_filter($cards, function($card) use ($excludedIds) {
            // Fonction de filtrage pour vérifier si l'ID de la carte est dans la liste des IDs exclus
            return !in_array($card->getId(), $excludedIds);
        });

        // Rendu du template Twig avec les données récupérées
        return $this->render('football/championnat/index.html.twig', [
            'championnats' => $championnats, // Passage des championnats au template
            'cards' => $filteredCards, // Passage des cartes filtrées au template
        ]);
    }
}
