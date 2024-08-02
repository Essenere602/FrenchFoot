<?php 
// src/Service/ChampionnatDataService.php

namespace App\Service;

use App\Repository\ChampionnatRepository;
use App\Repository\ClubRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;

class ChampionnatApiService extends AbstractController
{
    private $championnatRepository;
    private $paginator;
    private $clubRepository;

    public function __construct(ClubRepository $clubRepository,ChampionnatRepository $championnatRepository, PaginatorInterface $paginator)
    {
        $this->championnatRepository = $championnatRepository;
        $this->paginator = $paginator;
        $this->clubRepository = $clubRepository;
    }
    public function getChampionnatData(int $id, int $page = 1, int $limit = 10): array
    {
        $championnat = $this->championnatRepository->find($id);
    
        if (!$championnat) {
            throw $this->createNotFoundException('Le championnat n\'existe pas');
        }
    
        $apiToken = $_ENV['FOOTBALL_DATA_API_TOKEN'];
    
        $client = HttpClient::create([
            'base_uri' => 'https://api.football-data.org',
            'headers' => [
                'X-Auth-Token' => $apiToken,
            ],
        ]);
    
        try {
            $responseStandings = $client->request('GET', '/v4/competitions/' . $championnat->getCodeApi() . '/standings');
            $standings = $responseStandings->toArray();
            
            $responseMatches = $client->request('GET', '/v4/competitions/' . $championnat->getCodeApi() . '/matches');
            $matches = $responseMatches->toArray();
            
        } catch (ExceptionInterface $e) {
            $errorMessage = sprintf('Error fetching data for %s: %s', $championnat->getLigue(), $e->getMessage());
            $standings = ['standings' => []]; // Assurez-vous que c'est un tableau avec une clé 'standings'
            $matches = ['matches' => []]; // Assurez-vous que c'est un tableau avec une clé 'matches'
        }
    
        // Associer les logos aux clubs
        $this->addLogosToStandings($standings);
    
        // Paginate matches
        $pagination = $this->paginator->paginate(
            $matches['matches'], // array of items
            $page, // current page number
            $limit // limit per page
        );
    
        return [
            'championnat' => $championnat,
            'standings' => $standings,
            'matches' => $pagination->getItems(),
            'pagination' => $pagination,
        ];
    }
    private function addLogosToStandings(array &$standings): void
    {
        if (isset($standings['standings'][0]['table']) && is_array($standings['standings'][0]['table'])) {
            foreach ($standings['standings'][0]['table'] as &$team) {
                $club = $this->clubRepository->findOneBy(['name' => $team['team']['name']]);
                if ($club) {
                    $team['team']['logo'] = $club->getLogoClub(); // Ajouter le chemin du logo
                } else {
                    $team['team']['logo'] = 'media/default_logo.png'; // Logo par défaut
                }
            }
        } else {
            // Gestion des erreurs si 'standings' ou 'table' n'existe pas ou n'est pas un tableau
            $standings['standings'][0]['table'] = [];
        }
    }
}    