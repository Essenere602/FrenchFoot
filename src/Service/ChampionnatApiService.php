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

    public function getStandings(int $id): Response
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
        } catch (ExceptionInterface $e) {
            $errorMessage = sprintf('Error fetching data for %s: %s', $championnat->getLigue(), $e->getMessage());
            $standings = [];
        }

        // Associer les logos aux clubs
        $this->addLogosToStandings($standings);

        return $this->render('football/championnat_data/standings.html.twig', [
            'standings' => $standings,
            'championnat' => $championnat,
        ]);
    }

    private function addLogosToStandings(array &$standings): void
    {
        foreach ($standings['standings'][0]['table'] as &$team) {
            $club = $this->clubRepository->findOneBy(['name' => $team['team']['name']]);
            if ($club) {
                $team['team']['logo'] = $club->getLogoClub(); // Ajouter le chemin du logo
            } else {
                $team['team']['logo'] = 'media/default_logo.png'; // Logo par défaut
            }
        }
    }    public function getMatches(int $id, int $page = 1, int $limit = 10): Response
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
            $responseMatches = $client->request('GET', '/v4/competitions/' . $championnat->getCodeApi() . '/matches');
            $matches = $responseMatches->toArray();
        } catch (ExceptionInterface $e) {
            $errorMessage = sprintf('Error fetching data for %s: %s', $championnat->getLigue(), $e->getMessage());
            $matches = [];
        }

        $pagination = $this->paginator->paginate(
            $matches['matches'], // array of items
            $page, // current page number
            $limit // limit per page
        );

        return $this->render('football/championnat_data/matches.html.twig', [
            'matches' => $pagination->getItems(),
            'championnat' => $championnat,
            'pagination' => $pagination,
        ]);
    }
}