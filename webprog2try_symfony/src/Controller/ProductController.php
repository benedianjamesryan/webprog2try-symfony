<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    private const CATEGORIES = ['Phone Cooler', 'Laptop Cooler', 'Bundle'];

    #[Route('/products', name: 'app_products', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = mb_substr(trim((string) $request->query->get('q', '')), 0, 180);
        $category = (string) $request->query->get('category', '');
        $sort = (string) $request->query->get('sort', 'featured');
        $minPrice = $this->nonNegativeNumber($request->query->get('minPrice'));
        $maxPrice = $this->nonNegativeNumber($request->query->get('maxPrice'));
        $minRating = $this->boundedNumber($request->query->get('minRating'), 0, 5);
        $inStock = $request->query->getBoolean('inStock');

        if (!in_array($category, self::CATEGORIES, true)) {
            $category = '';
        }
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $queryBuilder = $entityManager->getRepository(Product::class)->createQueryBuilder('product');
        if ($category !== '') {
            $queryBuilder->andWhere('product.category = :category')->setParameter('category', $category);
        }
        if ($query !== '') {
            $queryBuilder->andWhere('(product.name LIKE :query OR product.description LIKE :query)')
                ->setParameter('query', '%'.$query.'%');
        }
        if ($minPrice !== null) {
            $queryBuilder->andWhere('product.price >= :minPrice')->setParameter('minPrice', number_format($minPrice, 2, '.', ''));
        }
        if ($maxPrice !== null) {
            $queryBuilder->andWhere('product.price <= :maxPrice')->setParameter('maxPrice', number_format($maxPrice, 2, '.', ''));
        }
        if ($minRating !== null) {
            $queryBuilder->andWhere('product.rating >= :minRating')->setParameter('minRating', number_format($minRating, 1, '.', ''));
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
        if (!isset($sortFields[$sort])) {
            $sort = 'featured';
        }
        [$sortField, $sortDirection] = $sortFields[$sort];
        $products = $queryBuilder->orderBy($sortField, $sortDirection)->getQuery()->getResult();

        return $this->render('products/index.html.twig', [
            'products' => $products,
            'categories' => self::CATEGORIES,
            'filters' => compact('query', 'category', 'sort', 'minPrice', 'maxPrice', 'minRating', 'inStock'),
        ]);
    }

    #[Route('/products/{id}', name: 'app_product_details', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            if (!$this->isCsrfTokenValid('review_'.$product->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid review form token.');
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }
            if ($entityManager->getRepository(Review::class)->findOneBy(['user' => $user, 'product' => $product]) instanceof Review) {
                $this->addFlash('error', 'You have already reviewed this product.');

                return $this->redirectToRoute('app_product_details', ['id' => $id]);
            }

            $rating = filter_var($request->request->get('rating'), FILTER_VALIDATE_INT);
            $text = trim((string) $request->request->get('review_text', ''));
            if ($rating === false || $rating < 1 || $rating > 5 || $text === '' || mb_strlen($text) > 1000) {
                $this->addFlash('error', 'Choose a rating from 1 to 5 and enter a review of up to 1,000 characters.');

                return $this->redirectToRoute('app_product_details', ['id' => $id]);
            }

            $review = new Review();
            $review->setUser($user);
            $review->setProduct($product);
            $review->setRating($rating);
            $review->setReviewText($text);
            $entityManager->persist($review);
            $entityManager->flush();
            $this->addFlash('success', 'Thanks for sharing your review.');

            return $this->redirectToRoute('app_product_details', ['id' => $id]);
        }

        $reviews = $entityManager->getRepository(Review::class)->findBy(['product' => $product], ['createdAt' => 'DESC']);

        return $this->render('products/show.html.twig', [
            'product' => $product,
            'reviews' => $reviews,
        ]);
    }

    private function nonNegativeNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) && is_finite((float) $value) && (float) $value >= 0 && (float) $value < 100000000
            ? (float) $value
            : null;
    }

    private function boundedNumber(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) && (float) $value >= $min && (float) $value <= $max
            ? (float) $value
            : null;
    }
}
