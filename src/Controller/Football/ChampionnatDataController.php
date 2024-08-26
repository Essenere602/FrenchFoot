<?php
// src/Controller/Football/ChampionnatDataController.php

namespace App\Controller\Football;

use App\Service\ChampionnatApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChampionnatDataController extends AbstractController
{
    private $championnatDataService;

    public function __construct(ChampionnatApiService $championnatDataService)
    {
        $this->championnatDataService = $championnatDataService;
    }

    #[Route('/championnats/{id}', name: 'championnat_data', methods: ['GET'])]
    public function championnatData(int $id, Request $request): Response
    {
        $matchday = $request->query->getInt('matchday', 1); // 1 par défaut si non défini
        $data = $this->championnatDataService->getChampionnatData($id, $matchday);

        return $this->render('football/championnat_data/index.html.twig', [
            'championnat' => $data['championnat'],
            'standings' => $data['standings'],
            'matches' => $data['matches'],
            'selectedMatchday' => $matchday,
        ]);
    }
}

