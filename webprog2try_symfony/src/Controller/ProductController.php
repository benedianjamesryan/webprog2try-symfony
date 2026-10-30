<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $query = mb_substr(trim((string) $request->query->get('q', '')), 0, 180);
        $category = (string) $request->query->get('category', '');
        $sort = (string) $request->query->get('sort', 'featured');
        $minPrice = $this->nonNegativeNumber($request->query->get('minPrice'));
        $maxPrice = $this->nonNegativeNumber($request->query->get('maxPrice'));
        $minRating = $this->boundedNumber($request->query->get('minRating'), 0, 5);
        $inStock = $request->query->getBoolean('inStock');

        if (!in_array($category, ProductRepository::CATEGORIES, true)) {
            $category = '';
        }
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }
        if (!in_array($sort, ['price_asc', 'price_desc', 'rating_desc', 'name', 'featured'], true)) {
            $sort = 'featured';
        }
        $products = $productRepository->findForCatalog($query, $category, $minPrice, $maxPrice, $minRating, $inStock, $sort);

        return $this->render('products/index.html.twig', [
            'products' => $products,
            'categories' => ProductRepository::CATEGORIES,
            'filters' => compact('query', 'category', 'sort', 'minPrice', 'maxPrice', 'minRating', 'inStock'),
        ]);
    }

    #[Route('/products/{id}', name: 'app_product_details', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product || !$product->isVisible()) {
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
