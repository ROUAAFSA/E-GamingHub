<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $session = $this->container->get('request_stack')->getSession();
        $user = $session->get('user');
        
        if (!$user) {
            return $this->redirectToRoute('app_sign_in');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }
}
