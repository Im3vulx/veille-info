<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Retourne les catégories racines (sans parent) triées par nom.
     *
     * @return Category[]
     */
    public function findRoots(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les catégories enfants (avec un parent) triées par nom.
     *
     * @return Category[]
     */
    public function findChildren(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent IS NOT NULL')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
