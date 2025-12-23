<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function countPublished(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.published = :status')
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countArticlesByCategory(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('c.id AS category_id, c.name AS category_name, COUNT(a.id) AS article_count')
            ->join('a.category', 'c')
            ->groupBy('c.id')
            ->orderBy('article_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.title LIKE :query')
            ->orWhere('a.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
