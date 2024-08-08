<?php
// src/Repository/PostRepository.php
// src/Repository/PostRepository.php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class PostRepository extends ServiceEntityRepository
    {
        private PaginatorInterface $paginator;
        private UserRepository $userRepository;
        private Security $security;
    
        public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator, UserRepository $userRepository, Security $security)
        {
            parent::__construct($registry, Post::class);
            $this->paginator = $paginator;
            $this->userRepository = $userRepository;
            $this->security = $security;
        }
    
        public function findByTopicPaginated(User $currentUser, int $topicId, int $page, int $limit = 10)
        {
            $queryBuilder = $this->createQueryBuilder('p')
                ->where('p.topic = :topic')
                ->setParameter('topic', $topicId)
                ->orderBy('p.id', 'DESC');
        
            // Utiliser isGranted pour vérifier si l'utilisateur a le rôle d'administrateur
            if (!$this->security->isGranted('ROLE_ADMIN', $currentUser)) {
                $blockedUsers = $this->userRepository->findBlockedUsers($currentUser);
                $blockedUserIds = array_map(fn($user) => $user->getId(), $blockedUsers);
        
                if (!empty($blockedUserIds)) {
                    $queryBuilder->andWhere('p.user NOT IN (:blockedUsers)')
                                 ->setParameter('blockedUsers', $blockedUserIds);
                }
            }
        
            return $this->paginator->paginate(
                $queryBuilder->getQuery(),
                $page,
                $limit
            );
        }
}

