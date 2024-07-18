<?php
// src/Controller/ChampionnatDataController.php

namespace App\Controller\Football;

use App\Service\ChampionnatApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChampionnatDataController extends AbstractController
{
    private $championnatDataService;

    public function __construct(ChampionnatApiService $championnatDataService)
    {
        $this->championnatDataService = $championnatDataService;
    }

    #[Route('/championnats/{id}/standings', name: 'championnat_data_standings', methods: ['GET'])]
    public function standings(int $id): Response
    {
        return $this->championnatDataService->getStandings($id);
    }

    #[Route('/championnats/{id}/matches', name: 'championnat_data_matches', methods: ['GET'])]
    public function matches(int $id): Response
    {
        return $this->championnatDataService->getMatches($id);
    }
}
