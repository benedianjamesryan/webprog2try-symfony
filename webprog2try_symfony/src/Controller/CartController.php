<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->currentUser();
        $items = $entityManager->getRepository(CartItem::class)->findBy(['user' => $user], ['id' => 'DESC']);
        $totalCents = 0;
        $cartLines = [];

        foreach ($items as $item) {
            $priceCents = $this->toCents($item->getProduct()->getPrice());
            $lineCents = $priceCents * $item->getQuantity();
            $totalCents += $lineCents;
            $cartLines[] = [
                'item' => $item,
                'line_total' => $this->formatCents($lineCents),
            ];
        }

        return $this->render('cart/index.html.twig', [
            'cartLines' => $cartLines,
            'total' => $this->formatCents($totalCents),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->currentUser();
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }
        if (!$this->isCsrfTokenValid('cart_add_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid cart form token.');
        }

        $quantity = filter_var($request->request->get('quantity', 1), FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1) {
            $this->addFlash('error', 'Enter a quantity of at least one.');

            return $this->redirectToRoute('app_product_details', ['id' => $id]);
        }

        $item = $entityManager->getRepository(CartItem::class)->findOneBy(['user' => $user, 'product' => $product]);
        $newQuantity = $quantity + ($item instanceof CartItem ? $item->getQuantity() : 0);
        if ($newQuantity > $product->getStock()) {
            $this->addFlash('error', 'The requested quantity is not available in stock.');

            return $this->redirectToRoute('app_product_details', ['id' => $id]);
        }

        if (!$item instanceof CartItem) {
            $item = new CartItem();
            $item->setUser($user);
            $item->setProduct($product);
            $entityManager->persist($item);
        }
        $item->setQuantity($newQuantity);
        $entityManager->flush();
        $this->addFlash('success', 'Added to your cart.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/{id}/quantity', name: 'app_cart_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $item = $this->ownedCartItem($id, $entityManager);
        if (!$this->isCsrfTokenValid('cart_update_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid cart form token.');
        }

        $quantity = filter_var($request->request->get('quantity'), FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1 || $quantity > $item->getProduct()->getStock()) {
            $this->addFlash('error', 'Choose a quantity between one and the available stock.');

            return $this->redirectToRoute('app_cart');
        }

        $item->setQuantity($quantity);
        $entityManager->flush();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/{id}/remove', name: 'app_cart_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $item = $this->ownedCartItem($id, $entityManager);
        if (!$this->isCsrfTokenValid('cart_remove_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid cart form token.');
        }

        $entityManager->remove($item);
        $entityManager->flush();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->currentUser();
        if (!$this->isCsrfTokenValid('cart_clear', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid cart form token.');
        }

        foreach ($entityManager->getRepository(CartItem::class)->findBy(['user' => $user]) as $item) {
            $entityManager->remove($item);
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_cart');
    }

    private function ownedCartItem(int $id, EntityManagerInterface $entityManager): CartItem
    {
        $item = $entityManager->getRepository(CartItem::class)->find($id);
        if (!$item instanceof CartItem || $item->getUser()->getId() !== $this->currentUser()->getId()) {
            throw $this->createNotFoundException('Cart item not found.');
        }

        return $item;
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function toCents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',');
    }
}
