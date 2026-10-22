<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/my-orders', name: 'app_my_orders', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->currentUser();

        return $this->render('orders/index.html.twig', [
            'orders' => $entityManager->getRepository(Order::class)->findBy(['user' => $user], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/my-orders/{id}', name: 'app_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order || $order->getUser()->getId() !== $this->currentUser()->getId()) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'items' => $entityManager->getRepository(OrderItem::class)->findBy(['order' => $order], ['id' => 'ASC']),
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
