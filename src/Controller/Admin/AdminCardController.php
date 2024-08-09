<?php

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/card')]
class AdminCardController extends AbstractController
{
    #[Route('/', name: 'app_admin_card_index', methods: ['GET'])]
    public function index(Request $request, CardRepository $cardRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $cardRepository->createQueryBuilder('c');

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Le numéro de la page actuelle
            10 // Nombre d'éléments par page
        );

        return $this->render('admin/admin_card/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_card_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $card = new Card();
        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('media_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $card->setImage('media/' . $newFilename);
            }

            $entityManager->persist($card);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_card_index');
        }

        return $this->render('admin/admin_card/new.html.twig', [
            'card' => $card,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_card_show', methods: ['GET'])]
    public function show(Card $card): Response
    {
        return $this->render('admin/admin_card/show.html.twig', [
            'card' => $card,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_card_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Card $card, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
    
            if ($imageFile) {
                // Supprimer l'ancienne image
                $existingImage = $card->getImage();
                if ($existingImage) {
                    $oldImagePath = $this->getParameter('media_directory') . '/' . basename($existingImage);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
    
                // Processus de traitement et de téléchargement de la nouvelle image
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('media_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }
    
                $card->setImage('media/' . $newFilename);
            }
    
            $entityManager->flush();
    
            return $this->redirectToRoute('app_admin_card_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('admin/admin_card/edit.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }
    
    #[Route('/{id}', name: 'app_admin_card_delete', methods: ['POST'])]
    public function delete(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$card->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($card);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_card_index', [], Response::HTTP_SEE_OTHER);
    }
}
