<?php
// Déclaration du namespace pour organiser le code. Ici, il est utilisé pour les contrôleurs liés au football.
namespace App\Controller\Football;

// Importation des classes nécessaires
use App\Service\ChampionnatApiService; // Importation du service ChampionnatApiService pour accéder aux données des championnats via une API
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour gérer les réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Importation de l'annotation Route pour définir les routes

// Définition du contrôleur pour les données des championnats
class ChampionnatDataController extends AbstractController
{
    private $championnatDataService; // Déclaration d'une propriété pour stocker le service de données des championnats

    // Constructeur pour injecter le service ChampionnatApiService
    public function __construct(ChampionnatApiService $championnatDataService)
    {
        $this->championnatDataService = $championnatDataService; // Initialisation de la propriété avec le service injecté
    }

    // Route pour afficher les données d'un championnat spécifique, accessible via une requête GET
    #[Route('/championnats/{id}', name: 'championnat_data', methods: ['GET'])]
    public function championnatData(int $id, Request $request): Response
    {
        // Récupération du paramètre 'matchday' depuis la requête, avec une valeur par défaut de 1 si non défini
        $matchday = $request->query->getInt('matchday', 1);

        // Appel du service pour obtenir les données du championnat pour l'ID donné et la journée spécifiée
        $data = $this->championnatDataService->getChampionnatData($id, $matchday);

        // Rendu du template Twig avec les données récupérées
        return $this->render('football/championnat_data/index.html.twig', [
            'championnat' => $data['championnat'], // Passage des données du championnat au template
            'standings' => $data['standings'], // Passage des classements au template
            'matches' => $data['matches'], // Passage des matchs au template
            'selectedMatchday' => $matchday, // Passage de la journée sélectionnée au template
        ]);
    }
}


