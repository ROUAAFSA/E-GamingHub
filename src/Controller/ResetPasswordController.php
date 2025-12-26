<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;
    private $mailer;
    private $passwordHasher;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    #[Route('/reset-password-request', name: 'app_forgot_password_request', methods: ['POST'])]
    public function request(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;

            if (!$email) {
                return $this->json(['success' => false, 'error' => 'Email requis']);
            }

            $this->logger->info('Demande de réinitialisation de mot de passe pour: ' . $email);

            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->logger->info('Utilisateur non trouvé pour l\'email: ' . $email);
                return $this->json(['success' => true, 'message' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.']);
            }

            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

            // Créer l'URL de réinitialisation
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->logger->info('URL de réinitialisation générée: ' . $resetUrl);

            // Créer et envoyer l'email
            $email = (new TemplatedEmail())
                ->from('no-reply@esportify.com')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context([
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'expiresAt' => $user->getResetTokenExpiresAt()->format('H:i')
                ]);

            $this->logger->info('Tentative d\'envoi d\'email à: ' . $user->getEmail());
            
            try {
                $this->mailer->send($email);
                $this->entityManager->flush();
                $this->logger->info('Email envoyé avec succès à: ' . $user->getEmail());
                return $this->json(['success' => true, 'message' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.']);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
                return $this->json(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur générale: ' . $e->getMessage());
            return $this->json(['success' => false, 'error' => 'Une erreur est survenue']);
        }
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $token): Response
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['resetToken' => $token]);

        // Vérifier si le token est valide et non expiré
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_sign_in');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->render('reset_password/reset.html.twig');
            }

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('reset_password/reset.html.twig');
            }

            // Hasher et sauvegarder le nouveau mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            // Effacer le token de réinitialisation
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_sign_in');
        }

        return $this->render('reset_password/reset.html.twig');
    }
} 