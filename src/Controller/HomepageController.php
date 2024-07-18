<?php
// src/Controller/HomepageController.php
namespace App\Controller;

use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $cards = $entityManager->getRepository(Card::class)->findAll();

        return $this->render('homepage/index.html.twig', [
            'cards' => $cards,
        ]);
    } 
}
