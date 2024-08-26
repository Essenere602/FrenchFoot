<?php 
// src/Service/ChampionnatApiService.php

namespace App\Service;

use App\Repository\ChampionnatRepository;
use App\Repository\ClubRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChampionnatApiService extends AbstractController
{
    private $championnatRepository;
    private $clubRepository;

    public function __construct(ClubRepository $clubRepository, ChampionnatRepository $championnatRepository)
    {
        $this->championnatRepository = $championnatRepository;
        $this->clubRepository = $clubRepository;
    }

    public function getChampionnatData(int $id, int $matchday = null): array
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
            
            $matchdayParam = $matchday ? '?matchday=' . $matchday : '';
            $responseMatches = $client->request('GET', '/v4/competitions/' . $championnat->getCodeApi() . '/matches' . $matchdayParam);
            $matches = $responseMatches->toArray();
            
        } catch (ExceptionInterface $e) {
            $errorMessage = sprintf('Error fetching data for %s: %s', $championnat->getLigue(), $e->getMessage());
            $standings = ['standings' => []];
            $matches = ['matches' => []];
        }
    
        // Associer les logos aux clubs
        //$this->addLogosToStandings($standings);
    
        return [
            'championnat' => $championnat,
            'standings' => $standings,
            'matches' => $matches['matches'],
        ];
    }

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
