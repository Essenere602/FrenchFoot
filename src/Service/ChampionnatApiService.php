<?php 
// src/Service/ChampionnatDataService.php

namespace App\Service;

use App\Repository\ChampionnatRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChampionnatApiService extends AbstractController
{
    private $championnatRepository;

    public function __construct(ChampionnatRepository $championnatRepository)
    {
        $this->championnatRepository = $championnatRepository;
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

        return $this->render('football/championnat_data/standings.html.twig', [
            'standings' => $standings,
            'championnat' => $championnat,
        ]);
    }

    public function getMatches(int $id): Response
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

        return $this->render('football/championnat_data/matches.html.twig', [
            'matches' => $matches,
            'championnat' => $championnat,
        ]);
    }
}
