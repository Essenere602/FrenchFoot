<?php

// src/Controller/UserController.php

namespace App\Controller\Messaging;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/autocomplete', name: 'app_user_autocomplete')]
    public function autocomplete(Request $request, UserRepository $userRepository): JsonResponse
    {
        $term = $request->query->get('term');
        $users = $userRepository->findByUsernameLike($term);

        $results = [];
        foreach ($users as $user) {
            $results[] = ['id' => $user->getId(), 'username' => $user->getUsername()];
        }

        return new JsonResponse($results);
    }
}
