<?php
// src/Controller/Football/ChampionnatController.php
namespace App\Controller\Football;

use App\Repository\CardRepository; // Assurez-vous d'avoir ce repository
use App\Repository\ChampionnatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChampionnatController extends AbstractController
{
    #[Route('/championnats', name: 'championnat_index', methods: ['GET'])]
    public function index(ChampionnatRepository $championnatRepository, CardRepository $cardRepository): Response
    {
        // Récupérer tous les championnats
        $championnats = $championnatRepository->findAll();
        
        // Récupérer toutes les cartes
        $cards = $cardRepository->findAll();

        // Filtrer les cartes pour exclure celles avec les IDs spécifiques
        $excludedIds = [34, 35, 36];
        $filteredCards = array_filter($cards, function($card) use ($excludedIds) {
            return !in_array($card->getId(), $excludedIds);
        });

        return $this->render('football/championnat/index.html.twig', [
            'championnats' => $championnats,
            'cards' => $filteredCards,
        ]);
    }
}


