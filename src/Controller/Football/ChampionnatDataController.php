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

#[Route('/championnats/{id}/{page}', name: 'championnat_data', methods: ['GET'], requirements: ['page' => '\d+'])]
public function championnatData(int $id, int $page = 1): Response
{
    $data = $this->championnatDataService->getChampionnatData($id, $page);

    return $this->render('football/championnat_data/index.html.twig', [
        'championnat' => $data['championnat'],
        'standings' => $data['standings'],
        'matches' => $data['matches'],
        'pagination' => $data['pagination'],
    ]);
}


}