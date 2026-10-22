<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Form\BrandType;
use App\Repository\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/brands', name: 'admin_brand_')]
final class BrandController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    public function index(BrandRepository $brandRepository): Response
    {
        return $this->render('admin/brands/index.html.twig', [
            'brands' => $brandRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $brand = new Brand();
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($brand);
            $entityManager->flush();
            $this->addFlash('success', 'Brand created.');

            return $this->redirectToRoute('admin_brand_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/brands/new.html.twig', [
            'brand' => $brand,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Brand $brand): Response
    {
        return $this->render('admin/brands/show.html.twig', [
            'brand' => $brand,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Brand $brand, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Brand updated.');

            return $this->redirectToRoute('admin_brand_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/brands/edit.html.twig', [
            'brand' => $brand,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Brand $brand, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$brand->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid brand form token.');
        }

        $entityManager->remove($brand);
        $entityManager->flush();
        $this->addFlash('success', 'Brand deleted.');

        return $this->redirectToRoute('admin_brand_index', [], Response::HTTP_SEE_OTHER);
    }
}
