<?php
// src/Repository/PostRepository.php
// src/Repository/PostRepository.php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\User;

class PostRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;
    private UserRepository $userRepository;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator, UserRepository $userRepository)
    {
        parent::__construct($registry, Post::class);
        $this->paginator = $paginator;
        $this->userRepository = $userRepository;
    }

    public function findByTopicPaginated(User $currentUser, int $topicId, int $page, int $limit = 10)
    {
        $blockedUsers = $this->userRepository->findBlockedUsers($currentUser);
        $blockedUserIds = array_map(fn($user) => $user->getId(), $blockedUsers);

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.topic = :topic')
            ->setParameter('topic', $topicId)
            ->andWhere('p.user NOT IN (:blockedUsers)')
            ->setParameter('blockedUsers', $blockedUserIds)
            ->orderBy('p.id', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            $limit
        );
    }
}

