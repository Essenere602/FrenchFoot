<?php

namespace App\Controller;

// Importation des classes nécessaires pour le contrôleur et les réponses HTTP.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe AbstractController pour les contrôleurs Symfony.
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour les réponses HTTP.
use Symfony\Component\Routing\Attribute\Route; // Importation de l'attribut Route pour la définition des routes.

class PrivacyPoliceController extends AbstractController
{
    #[Route('/privacy/police', name: 'app_privacy_police')] // Définition de la route pour la page de la politique de confidentialité.
    public function index(): Response
    {
        // Rendu de la vue de la politique de confidentialité avec un nom de contrôleur comme variable.
        return $this->render('privacy_police/index.html.twig', [
            'controller_name' => 'PrivacyPoliceController', // Passage du nom du contrôleur à la vue pour affichage.
        ]);
    }
}
