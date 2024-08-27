<?php
// src/Controller/HomepageController.php

namespace App\Controller;

// Importation des classes nécessaires pour la gestion des entités et des requêtes.
use App\Entity\Card; // Importation de l'entité Card représentant les cartes.
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface pour la gestion des entités Doctrine.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\Routing\Annotation\Route; // Importation de l'annotation Route pour la définition des routes.

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')] // Définition de la route pour la page d'accueil.
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupération de toutes les cartes depuis la base de données.
        $cards = $entityManager->getRepository(Card::class)->findAll();

        // Rendu de la vue de la page d'accueil avec les cartes récupérées.
        return $this->render('homepage/index.html.twig', [
            'cards' => $cards, // Passage des cartes à la vue pour affichage.
        ]);
    } 
}
