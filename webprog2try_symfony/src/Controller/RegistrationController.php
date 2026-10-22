<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid registration form token.');
            }

            $fullName = trim((string) $request->request->get('full_name', ''));
            $email = mb_strtolower(trim((string) $request->request->get('email', '')));
            $password = (string) $request->request->get('password', '');
            $confirmation = (string) $request->request->get('password_confirmation', '');
            $termsAccepted = $request->request->getBoolean('terms');

            if ($fullName === '' || mb_strlen($fullName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || mb_strlen($email) > 190 || mb_strlen($password) < 8 || $password !== $confirmation
                || !$termsAccepted) {
                $this->addFlash('error', 'Check your name, email, password confirmation, and terms acceptance. Passwords need at least 8 characters.');

                return $this->redirectToRoute('app_register');
            }
            if ($entityManager->getRepository(User::class)->findOneBy(['email' => $email]) instanceof User) {
                $this->addFlash('error', 'An account already exists for that email address.');

                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setFullName($fullName);
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setRole('customer');
            $user->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been created. Sign in to continue.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('register/index.html.twig');
    }
}
