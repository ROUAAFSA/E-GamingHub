<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class TestMailerController extends AbstractController
{
    #[Route('/test/mailer', name: 'app_test_mailer')]
    public function testMail(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        try {
            $email = (new Email())
                ->from('no-reply@esportify.com')
                ->to('test@example.com')
                ->subject('Test Email')
                ->text('This is a test email to verify mailer configuration.');

            $logger->info('Tentative d\'envoi d\'un email de test');
            $mailer->send($email);
            $logger->info('Email de test envoyé avec succès');

            return new Response('Email envoyé avec succès! Vérifiez Mailtrap.');
        } catch (\Exception $e) {
            $logger->error('Erreur lors de l\'envoi de l\'email de test: ' . $e->getMessage());
            return new Response('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }
} 