<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public const CATEGORIES = ['Phone Cooler', 'Laptop Cooler', 'Bundle'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findForCatalog(
        string $search,
        string $category,
        ?float $minPrice,
        ?float $maxPrice,
        ?float $minRating,
        bool $inStock,
        string $sort,
    ): array {
        $queryBuilder = $this->createQueryBuilder('product')
            ->andWhere('product.visible = :visible')
            ->setParameter('visible', true);

        if ($category !== '') {
            $queryBuilder->andWhere('product.category = :category')
                ->setParameter('category', $category);
        }
        if ($search !== '') {
            $queryBuilder->andWhere('(product.name LIKE :search OR product.description LIKE :search)')
                ->setParameter('search', '%'.$search.'%');
        }
        if ($minPrice !== null) {
            $queryBuilder->andWhere('product.price >= :minPrice')
                ->setParameter('minPrice', number_format($minPrice, 2, '.', ''));
        }
        if ($maxPrice !== null) {
            $queryBuilder->andWhere('product.price <= :maxPrice')
                ->setParameter('maxPrice', number_format($maxPrice, 2, '.', ''));
        }
        if ($minRating !== null) {
            $queryBuilder->andWhere('product.rating >= :minRating')
                ->setParameter('minRating', number_format($minRating, 1, '.', ''));
        }
        if ($inStock) {
            $queryBuilder->andWhere('product.stock > 0');
        }

        $sortFields = [
            'price_asc' => ['product.price', 'ASC'],
            'price_desc' => ['product.price', 'DESC'],
            'rating_desc' => ['product.rating', 'DESC'],
            'name' => ['product.name', 'ASC'],
            'featured' => ['product.id', 'ASC'],
        ];
        [$sortField, $sortDirection] = $sortFields[$sort] ?? $sortFields['featured'];

        return $queryBuilder->orderBy($sortField, $sortDirection)->getQuery()->getResult();
    }

    /**
     * @return Product[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('product')
            ->andWhere('product.category = :category')
            ->setParameter('category', $category)
            ->orderBy('product.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function findForAdmin(string $search, string $category): array
    {
        $queryBuilder = $this->createQueryBuilder('product');
        if ($search !== '') {
            $queryBuilder->andWhere('product.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }
        if ($category !== '') {
            $queryBuilder->andWhere('product.category = :category')
                ->setParameter('category', $category);
        }

        return $queryBuilder->orderBy('product.id', 'ASC')->getQuery()->getResult();
    }
}
