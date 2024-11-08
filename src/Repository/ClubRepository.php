<?php

namespace App\Repository;

use App\Entity\Club;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Club>
 */
class ClubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Club::class);
    }

    public function searchClubsByName(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->getQuery()
            ->getResult();
    }
}
