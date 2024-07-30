<?php
// src/Controller/Profile/ProfilePasswordController.php

namespace App\Controller\Profile;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfilePasswordController extends AbstractController
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérification de l'ancien mot de passe
            $currentPassword = $form->get('current_password')->getData();
            if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
                // Si l'ancien mot de passe n'est pas valide, ajouter un message d'erreur
                $this->addFlash('error', 'L\'ancien mot de passe est incorrect.');
            } else {
                // Si l'ancien mot de passe est valide, mettre à jour avec le nouveau mot de passe
                $newPassword = $this->hasher->hashPassword($user, $data['plainpassword']);
                $user->setPassword($newPassword);
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');

                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'formPass' => $form->createView(),
        ]);
    }
}


