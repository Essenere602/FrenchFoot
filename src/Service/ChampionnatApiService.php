<?php 
// src/Service/ChampionnatApiService.php

namespace App\Service;

// Importation des classes nécessaires pour les interactions avec les repositories et les appels HTTP.
use App\Repository\ChampionnatRepository; // Importation du repository pour accéder aux données des championnats.
use App\Repository\ClubRepository; // Importation du repository pour accéder aux données des clubs.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\HttpClient\HttpClient; // Importation de la classe HttpClient pour effectuer des requêtes HTTP.
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface; // Importation de l'interface pour gérer les exceptions liées aux requêtes HTTP.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.

class ChampionnatApiService extends AbstractController
{
    private $championnatRepository; // Propriété pour le repository des championnats.
    private $clubRepository; // Propriété pour le repository des clubs.

    public function __construct(ClubRepository $clubRepository, ChampionnatRepository $championnatRepository)
    {
        $this->championnatRepository = $championnatRepository; // Initialisation du repository des championnats.
        $this->clubRepository = $clubRepository; // Initialisation du repository des clubs.
    }

    /**
     * Récupère les données du championnat, y compris les standings et les matchs.
     *
     * @param int $id Identifiant du championnat.
     * @param int|null $matchday Numéro du jour de match (optionnel).
     * @return array Les données du championnat, standings et matchs.
     */
    public function getChampionnatData(int $id, int $matchday = null): array
    {
        // Recherche du championnat dans le repository.
        $championnat = $this->championnatRepository->find($id);
    
        if (!$championnat) {
            // Lance une exception si le championnat n'existe pas.
            throw $this->createNotFoundException('Le championnat n\'existe pas');
        }
    
        // Récupération du token API depuis les variables d'environnement.
        $apiToken = $_ENV['FOOTBALL_DATA_API_TOKEN'];
    
        // Création du client HTTP avec les paramètres nécessaires.
        $client = HttpClient::create([
            'base_uri' => 'https://api.football-data.org',
            'headers' => [
                'X-Auth-Token' => $apiToken,
            ],
        ]);
    
        try {
            // Requête pour obtenir les standings du championnat.
            $responseStandings = $client->request('GET', '/v4/competitions/' . $championnat->getCodeApi() . '/standings');
            $standings = $responseStandings->toArray();
            
            // Requête pour obtenir les matchs du championnat, en ajoutant le paramètre matchday si présent.
            $matchdayParam = $matchday ? '?matchday=' . $matchday : '';
            $responseMatches = $client->request('GET', '/v4/competitions/' . $championnat->getCodeApi() . '/matches' . $matchdayParam);
            $matches = $responseMatches->toArray();
            
        } catch (ExceptionInterface $e) {
            // En cas d'erreur lors des appels API, définir des données vides pour standings et matchs.
            $errorMessage = sprintf('Error fetching data for %s: %s', $championnat->getLigue(), $e->getMessage());
            $standings = ['standings' => []];
            $matches = ['matches' => []];
        }
    
        // Associer les logos aux clubs (fonction commentée pour l'instant).
        //$this->addLogosToStandings($standings);
    
        return [
            'championnat' => $championnat,
            'standings' => $standings,
            'matches' => $matches['matches'],
        ];
    }

    // Fonction commentée pour ajouter les logos aux standings.
    //private function addLogosToStandings(array &$standings): void
    // {
    //     if (isset($standings['standings'][0]['table']) && is_array($standings['standings'][0]['table'])) {
    //         foreach ($standings['standings'][0]['table'] as &$team) {
    //             $club = $this->clubRepository->findOneBy(['name' => $team['team']['name']]);
    //             if ($club) {
    //                 $team['team']['logo'] = $club->getLogoClub();
    //             } else {
    //                 $team['team']['logo'] = 'media/default_logo.png';
    //             }
    //         }
    //     } else {
    //         $standings['standings'][0]['table'] = [];
    //     }
    // }
}
