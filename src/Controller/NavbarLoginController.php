<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NavbarLoginController extends AbstractController
{
    #[Route('/navbar/login', name: 'app_navbar_login')]
    public function index(): Response
    {
        return $this->render('navbar/navbar_login.html.twig');
    }
} 