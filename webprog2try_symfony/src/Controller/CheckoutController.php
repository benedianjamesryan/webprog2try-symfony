<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    private const PAYMENT_METHODS = ['COD', 'GCash', 'Card'];

    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->currentUser();
        $cartItems = $entityManager->getRepository(CartItem::class)->findBy(['user' => $user]);
        if ($cartItems === []) {
            $this->addFlash('error', 'Add products to your cart before checking out.');

            return $this->redirectToRoute('app_cart');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid checkout form token.');
            }

            $details = [
                'fullName' => trim((string) $request->request->get('full_name', '')),
                'email' => trim((string) $request->request->get('email', '')),
                'phone' => trim((string) $request->request->get('phone', '')),
                'address' => trim((string) $request->request->get('address', '')),
                'city' => trim((string) $request->request->get('city', '')),
                'province' => trim((string) $request->request->get('province', '')),
                'postalCode' => trim((string) $request->request->get('postal_code', '')),
                'paymentMethod' => (string) $request->request->get('payment_method', ''),
            ];
            $lengthLimits = [
                'fullName' => 150, 'email' => 190, 'phone' => 40, 'address' => 255,
                'city' => 100, 'province' => 100, 'postalCode' => 20,
            ];
            $valid = true;
            foreach ($lengthLimits as $key => $limit) {
                if ($details[$key] === '' || mb_strlen($details[$key]) > $limit) {
                    $valid = false;
                    break;
                }
            }
            if (!filter_var($details['email'], FILTER_VALIDATE_EMAIL) || !in_array($details['paymentMethod'], self::PAYMENT_METHODS, true)) {
                $valid = false;
            }
            if (!$valid) {
                $this->addFlash('error', 'Check the delivery details and choose a valid payment method.');

                return $this->redirectToRoute('app_checkout');
            }

            try {
                $order = $entityManager->wrapInTransaction(function (EntityManagerInterface $manager) use ($user, $details): Order {
                    $items = $manager->getRepository(CartItem::class)->findBy(['user' => $user]);
                    if ($items === []) {
                        throw new DomainException('Your cart is empty.');
                    }

                    $order = new Order();
                    $order->setUser($user);
                    $order->setOrderNumber('FC-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4))));
                    $order->setFullName($details['fullName']);
                    $order->setEmail($details['email']);
                    $order->setPhone($details['phone']);
                    $order->setAddress($details['address']);
                    $order->setCity($details['city']);
                    $order->setProvince($details['province']);
                    $order->setPostalCode($details['postalCode']);
                    $order->setPaymentMethod($details['paymentMethod']);

                    $subtotalCents = 0;
                    foreach ($items as $cartItem) {
                        $product = $manager->find(Product::class, $cartItem->getProduct()->getId(), LockMode::PESSIMISTIC_WRITE);
                        if (!$product instanceof Product || $cartItem->getQuantity() > $product->getStock()) {
                            throw new DomainException(sprintf('%s no longer has enough stock.', $cartItem->getProduct()->getName()));
                        }

                        $priceCents = (int) round((float) $product->getPrice() * 100);
                        $lineCents = $priceCents * $cartItem->getQuantity();
                        $subtotalCents += $lineCents;

                        $orderItem = new OrderItem();
                        $orderItem->setOrder($order);
                        $orderItem->setProduct($product);
                        $orderItem->setProductName($product->getName());
                        $orderItem->setPrice(number_format($priceCents / 100, 2, '.', ''));
                        $orderItem->setQuantity($cartItem->getQuantity());
                        $orderItem->setSubtotal(number_format($lineCents / 100, 2, '.', ''));
                        $manager->persist($orderItem);

                        $product->setStock($product->getStock() - $cartItem->getQuantity());
                        $manager->remove($cartItem);
                    }

                    $order->setSubtotal(number_format($subtotalCents / 100, 2, '.', ''));
                    $order->setShippingFee('0.00');
                    $order->setTotal(number_format($subtotalCents / 100, 2, '.', ''));
                    $manager->persist($order);

                    return $order;
                });
            } catch (DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->redirectToRoute('app_cart');
            }

            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        $totalCents = 0;
        foreach ($cartItems as $cartItem) {
            $totalCents += (int) round((float) $cartItem->getProduct()->getPrice() * 100) * $cartItem->getQuantity();
        }

        return $this->render('checkout/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => number_format($totalCents / 100, 2, '.', ','),
            'account' => $user,
        ]);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
