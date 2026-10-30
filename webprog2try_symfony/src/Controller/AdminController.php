<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{
    private const ORDER_STATUSES = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
    private const PRODUCT_IMAGES = [
        'assets/products/fc1-cooler.svg',
        'assets/products/fc1-cooler.jpg',
        'assets/products/fc2-cooler.jpg',
        'assets/products/fc3-cooler.jpg',
        'assets/products/fc4-cooler.jpg',
        'assets/products/fc5-cooler.png',
        'assets/products/fc6-cooler.jpg',
        'assets/products/fc7-cooler.jpg',
        'assets/products/fc8-cooler.jpg',
    ];

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $orderRepository = $entityManager->getRepository(Order::class);
        $productRepository = $entityManager->getRepository(Product::class);
        $totalSales = $orderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->getQuery()
            ->getSingleScalarResult();
        $lowStock = $productRepository->createQueryBuilder('product')
            ->andWhere('product.stock <= :limit')
            ->setParameter('limit', 5)
            ->orderBy('product.stock', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $productRepository->count([]),
            'customerCount' => $entityManager->getRepository(User::class)->count(['role' => 'customer']),
            'orderCount' => $orderRepository->count([]),
            'pendingCount' => $orderRepository->count(['status' => 'Pending']),
            'outOfStockCount' => $productRepository->count(['stock' => 0]),
            'totalSales' => $totalSales,
            'lowStock' => $lowStock,
            'recentOrders' => $orderRepository->findBy([], ['createdAt' => 'DESC'], 6),
        ]);
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function products(Request $request, ProductRepository $productRepository): Response
    {
        $search = mb_substr(trim((string) $request->query->get('q', '')), 0, 180);
        $category = (string) $request->query->get('category', '');
        if (!in_array($category, ProductRepository::CATEGORIES, true)) {
            $category = '';
        }

        return $this->render('admin/products.html.twig', [
            'products' => $productRepository->findForAdmin($search, $category),
            'categories' => ProductRepository::CATEGORIES,
            'search' => $search,
            'category' => $category,
        ]);
    }

    #[Route('/products/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function newProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $product->setName('');
        $product->setCategory(ProductRepository::CATEGORIES[0]);
        $product->setDescription('');
        $product->setPrice('0.00');
        $product->setRating('0.0');
        $product->setImage(self::PRODUCT_IMAGES[0]);

        return $this->editProductForm($product, $request, $entityManager, true);
    }

    #[Route('/products/{id}/edit', name: 'product_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editProduct(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }

        return $this->editProductForm($product, $request, $entityManager, false);
    }

    #[Route('/products/{id}/visibility', name: 'product_visibility', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleProductVisibility(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }
        if (!$this->isCsrfTokenValid('admin_product_visibility_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid product form token.');
        }

        $product->setVisible(!$product->isVisible());
        $entityManager->flush();
        $this->addFlash('success', $product->isVisible() ? 'Product shown in the catalog.' : 'Product hidden from the catalog.');

        return $this->redirectToRoute('admin_products');
    }

    #[Route('/products/{id}/delete', name: 'product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteProduct(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }
        if (!$this->isCsrfTokenValid('admin_product_delete_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid product form token.');
        }
        if ($entityManager->getRepository(OrderItem::class)->count(['product' => $product]) > 0) {
            $this->addFlash('error', 'Products in order history cannot be deleted.');

            return $this->redirectToRoute('admin_products');
        }

        $entityManager->remove($product);
        $entityManager->flush();
        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('admin_products');
    }

    #[Route('/orders', name: 'orders', methods: ['GET', 'POST'])]
    public function orders(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $id = filter_var($request->request->get('order_id'), FILTER_VALIDATE_INT);
            if ($id === false || !$this->isCsrfTokenValid('admin_order_'.$id, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid order form token.');
            }

            $order = $entityManager->getRepository(Order::class)->find($id);
            $status = (string) $request->request->get('status', '');
            if (!$order instanceof Order || !in_array($status, self::ORDER_STATUSES, true)) {
                throw $this->createNotFoundException('Order or status not found.');
            }
            $order->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Order status updated.');

            return $this->redirectToRoute('admin_orders');
        }

        $status = (string) $request->query->get('status', '');
        $queryBuilder = $entityManager->getRepository(Order::class)->createQueryBuilder('o');
        if (in_array($status, self::ORDER_STATUSES, true)) {
            $queryBuilder->andWhere('o.status = :status')->setParameter('status', $status);
        } else {
            $status = '';
        }

        return $this->render('admin/orders.html.twig', [
            'orders' => $queryBuilder->orderBy('o.createdAt', 'DESC')->getQuery()->getResult(),
            'statuses' => self::ORDER_STATUSES,
            'status' => $status,
        ]);
    }

    #[Route('/orders/{id}', name: 'order_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function showOrder(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found.');
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_order_'.$id, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid order form token.');
            }
            $status = (string) $request->request->get('status', '');
            if (!in_array($status, self::ORDER_STATUSES, true)) {
                throw $this->createAccessDeniedException('Invalid order status.');
            }
            $order->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Order status updated.');

            return $this->redirectToRoute('admin_order_show', ['id' => $id]);
        }

        return $this->render('admin/order_show.html.twig', [
            'order' => $order,
            'items' => $entityManager->getRepository(OrderItem::class)->findBy(['order' => $order], ['id' => 'ASC']),
            'statuses' => self::ORDER_STATUSES,
        ]);
    }

    #[Route('/customers', name: 'customers', methods: ['GET'])]
    public function customers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $role = (string) $request->query->get('role', '');
        $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('user');
        if ($search !== '') {
            $queryBuilder->andWhere('user.fullName LIKE :search OR user.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }
        if (in_array($role, ['customer', 'admin'], true)) {
            $queryBuilder->andWhere('user.role = :role')->setParameter('role', $role);
        } else {
            $role = '';
        }

        return $this->render('admin/customers.html.twig', [
            'users' => $queryBuilder->orderBy('user.createdAt', 'DESC')->getQuery()->getResult(),
            'customerCount' => $entityManager->getRepository(User::class)->count(['role' => 'customer']),
            'adminCount' => $entityManager->getRepository(User::class)->count(['role' => 'admin']),
            'search' => $search,
            'role' => $role,
        ]);
    }

    #[Route('/reviews', name: 'reviews', methods: ['GET', 'POST'])]
    public function reviews(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $id = filter_var($request->request->get('review_id'), FILTER_VALIDATE_INT);
            if ($id === false || !$this->isCsrfTokenValid('admin_review_'.$id, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid review form token.');
            }

            $review = $entityManager->getRepository(Review::class)->find($id);
            if (!$review instanceof Review) {
                throw $this->createNotFoundException('Review not found.');
            }
            $entityManager->remove($review);
            $entityManager->flush();
            $this->addFlash('success', 'Review deleted.');

            return $this->redirectToRoute('admin_reviews');
        }

        return $this->render('admin/reviews.html.twig', [
            'reviews' => $entityManager->getRepository(Review::class)->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    private function editProductForm(Product $product, Request $request, EntityManagerInterface $entityManager, bool $isNew): Response
    {
        if ($request->isMethod('POST')) {
            $tokenId = $product->getId() ?? 'new';
            if (!$this->isCsrfTokenValid('admin_product_'.$tokenId, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid product form token.');
            }

            $name = trim((string) $request->request->get('name', ''));
            $category = (string) $request->request->get('category', '');
            $description = trim((string) $request->request->get('description', ''));
            $price = $request->request->get('price');
            $rating = $request->request->get('rating');
            $stock = filter_var($request->request->get('stock'), FILTER_VALIDATE_INT);
            $image = (string) $request->request->get('image', '');

            if ($name === '' || mb_strlen($name) > 180 || !in_array($category, self::CATEGORIES, true)
                || $description === '' || !is_numeric($price) || (float) $price < 0 || (float) $price >= 100000000
                || !is_numeric($rating) || (float) $rating < 0 || (float) $rating > 5
                || $stock === false || $stock < 0 || !in_array($image, self::PRODUCT_IMAGES, true)
                || !is_file($this->getParameter('kernel.project_dir').'/public/'.$image)) {
                $this->addFlash('error', 'Check each product field and choose a valid existing product image.');

                return $this->redirectToRoute($isNew ? 'admin_product_new' : 'admin_product_edit', $isNew ? [] : ['id' => $product->getId()]);
            }

            $product->setName($name);
            $product->setCategory($category);
            $product->setDescription($description);
            $product->setPrice(number_format((float) $price, 2, '.', ''));
            $product->setRating(number_format((float) $rating, 1, '.', ''));
            $product->setStock($stock);
            $product->setImage($image);
            if ($isNew) {
                $entityManager->persist($product);
            }
            $entityManager->flush();
            $this->addFlash('success', $isNew ? 'Product created.' : 'Product updated.');

            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/product_form.html.twig', [
            'product' => $product,
            'categories' => ProductRepository::CATEGORIES,
            'images' => self::PRODUCT_IMAGES,
            'isNew' => $isNew,
        ]);
    }
}
