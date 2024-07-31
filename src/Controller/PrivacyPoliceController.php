<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PrivacyPoliceController extends AbstractController
{
    #[Route('/privacy/police', name: 'app_privacy_police')]
    public function index(): Response
    {
        return $this->render('privacy_police/index.html.twig', [
            'controller_name' => 'PrivacyPoliceController',
        ]);
    }
}
