<?php
// src/Controller/Profile/ProfilePasswordController.php

namespace App\Controller\Profile;

// Importation des classes nécessaires pour la gestion du formulaire et du hashing de mot de passe
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

// Contrôleur pour la gestion du changement de mot de passe dans le profil utilisateur
class ProfilePasswordController extends AbstractController
{
    // Propriété pour stocker l'interface de hashing des mots de passe
    private UserPasswordHasherInterface $hasher;

    // Constructeur pour injecter la dépendance du hasher de mots de passe
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Route pour changer le mot de passe utilisateur
    #[Route('/profile/change-password', name: 'app_profile_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur actuellement connecté
        $user = $this->getUser();
        
        // Création du formulaire pour le changement de mot de passe
        $form = $this->createForm(ChangePasswordType::class);
        
        // Gestion de la requête et validation du formulaire
        $form->handleRequest($request);

        // Vérification si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des données du formulaire
            $data = $form->getData();

            // Vérification de l'ancien mot de passe
            $currentPassword = $form->get('current_password')->getData();
            if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
                // Si l'ancien mot de passe n'est pas valide, afficher un message d'erreur
                $this->addFlash('error', 'L\'ancien mot de passe est incorrect.');
            } else {
                // Si l'ancien mot de passe est valide, hashage et mise à jour avec le nouveau mot de passe
                $newPassword = $this->hasher->hashPassword($user, $data['plainpassword']);
                $user->setPassword($newPassword);

                // Persistance et enregistrement du nouvel état de l'utilisateur dans la base de données
                $entityManager->persist($user);
                $entityManager->flush();

                // Message de succès pour informer que le mot de passe a été mis à jour
                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');

                // Redirection vers la page de profil après succès
                return $this->redirectToRoute('app_profile');
            }
        }

        // Affichage du formulaire dans la vue
        return $this->render('profile/change_password.html.twig', [
            'formPass' => $form->createView(),
        ]);
    }
}
