<?php

// src/Controller/Profile/BlockController.php

namespace App\Controller\Profile;

use App\Entity\Block;
use App\Repository\UserRepository;
use App\Repository\BlockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class BlockController extends AbstractController
{
    #[Route('/profile/manage-blocks', name: 'app_manage_blocks')]
    public function manageBlocks(Request $request, Security $security, UserRepository $userRepository, BlockRepository $blockRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();
        $blocksInitiated = $user->getBlocksInitiated();

        if ($request->isMethod('POST')) {
            $blockUsername = $request->request->get('block_username');
            $blockedUser = $userRepository->findOneBy(['username' => $blockUsername]);

            if ($blockedUser) {
                if (in_array('ROLE_ADMIN', $blockedUser->getRoles())) {
                    $this->addFlash('error', 'Vous ne pouvez pas bloquer un administrateur.');
                } else {
                    $block = new Block();
                    $block->setBlocker($user);
                    $block->setBlocked($blockedUser);
                    $entityManager->persist($block);
                    $entityManager->flush();

                    $this->addFlash('success', 'Utilisateur bloqué avec succès.');
                }
            } else {
                $this->addFlash('error', 'Utilisateur introuvable.');
            }

            return $this->redirectToRoute('app_manage_blocks');
        }

        return $this->render('profile/manage_blocks.html.twig', [
            'blocksInitiated' => $blocksInitiated,
        ]);
    }
    #[Route('/profile/unblock/{id}', name: 'app_unblock_user')]
    public function unblockUser(int $id, Security $security, BlockRepository $blockRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();
        $block = $blockRepository->find($id);

        if ($block && $block->getBlocker() === $user) {
            $entityManager->remove($block);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur débloqué avec succès.');
        } else {
            $this->addFlash('error', 'Action non autorisée.');
        }

        return $this->redirectToRoute('app_manage_blocks');
    }

    #[Route('/profile/user-autocomplete', name: 'user_autocomplete')]
    public function userAutocomplete(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        $users = $userRepository->createQueryBuilder('u')
            ->where('u.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ];
        }

        return $this->json($results);
    }
}


