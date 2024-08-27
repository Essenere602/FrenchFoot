<?php

namespace App\Controller;

// Importation des classes nécessaires pour le contrôleur et les réponses HTTP.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\Routing\Attribute\Route; // Importation de l'attribut Route pour la définition des routes.

class LegalNoticeController extends AbstractController
{
    #[Route('/legal/notice', name: 'app_legal_notice')] // Définition de la route pour la page des mentions légales.
    public function index(): Response
    {
        // Rendu de la vue des mentions légales avec un nom de contrôleur comme variable.
        return $this->render('legal_notice/index.html.twig', [
            'controller_name' => 'LegalNoticeController', // Passage du nom du contrôleur à la vue pour affichage.
        ]);
    }
}
