<?php
// src/Controller/ChampionnatController.php
namespace App\Controller\Football;

use App\Repository\ChampionnatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChampionnatController extends AbstractController
{
    #[Route('/championnats', name: 'championnat_index', methods: ['GET'])]
    public function index(ChampionnatRepository $championnatRepository): Response
    {
        $championnats = $championnatRepository->findAll();

        return $this->render('football/championnat/index.html.twig', [
            'championnats' => $championnats,
        ]);
    }

}

