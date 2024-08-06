<?php
// src/Repository/UserRepository.php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Block;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByUsernameLike(string $username): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username LIKE :username')
            ->setParameter('username', '%' . $username . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
    public function findBlockedUsers(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->innerJoin('u.blocksReceived', 'b')
            ->where('b.blocker = :user')
            ->setParameter('user', $user)
            ->getQuery();

        return $queryBuilder->getResult();
    }
}

