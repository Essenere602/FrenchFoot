<?php

// Déclaration du namespace pour organiser le code dans le répertoire Controller/Admin
namespace App\Controller\Admin;

// Importation des classes nécessaires pour le contrôleur et les réponses HTTP
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Définition de la classe AdminController qui étend la classe AbstractController
class AdminController extends AbstractController
{
    // Définition de la route pour la méthode index avec le nom 'app_admin'
    // Cette route est accessible via l'URL '/admin'
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        // Rendu du template Twig 'admin/index.html.twig'
        // La variable 'controller_name' est passée au template avec la valeur 'AdminController'
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
