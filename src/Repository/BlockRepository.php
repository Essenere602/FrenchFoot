<?php

namespace App\Repository;

use App\Entity\Block;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Block>
 */
class BlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Block::class);
    }

    //    /**
    //     * @return Block[] Returns an array of Block objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Block
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function isBlocked(User $blocker, User $blocked): bool
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->where('b.blocker = :blocker')
            ->andWhere('b.blocked = :blocked')
            ->setParameter('blocker', $blocker)
            ->setParameter('blocked', $blocked)
            ->getQuery();

        return (bool) $queryBuilder->getOneOrNullResult();
    }
    public function isBlockedBy(User $user, User $blocked): bool
    {
        return $this->isBlocked($blocked, $user);
    }
}
