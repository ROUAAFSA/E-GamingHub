<?php

namespace App\Controller;

use App\Repository\PromotionRepository;
use App\Repository\ProduitRepository;
use App\Repository\CodePromoRepository;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/boutique')]
class BoutiqueController extends AbstractController
{
    #[Route('/', name: 'admin_boutique')]
    public function index(
        Request $request,
        PromotionRepository $promotionRepository,
        ProduitRepository $produitRepository,
        CodePromoRepository $codePromoRepository,
        CommandeRepository $commandeRepository
    ): Response
    {
        $section = $request->query->get('section', 'produits');

        // Valider que la section demandée est autorisée
        $sectionsValides = ['produits', 'commandes', 'dashboard', 'stock', 'promotions'];
        if (!in_array($section, $sectionsValides)) {
            $section = 'produits';
        }

        // Données de base communes à toutes les sections
        $data = [
            'section' => $section,
            'stats' => [],
            'chartData' => [],
        ];

        // Ajouter les données spécifiques selon la section
        switch ($section) {
            case 'produits':
                $data['produits'] = $produitRepository->findAll();
                break;

            case 'commandes':
                $data['commandes'] = $commandeRepository->findAll();
                break;

            case 'promotions':
                $data['promotions_produits'] = $promotionRepository->findAll();
                $data['produits'] = $produitRepository->findAll();
                $data['codes_promo'] = $codePromoRepository->findAll();
                break;

            case 'stock':
                $data['produits'] = $produitRepository->findAll();
                break;
        }

        return $this->render('admin/boutique.html.twig', $data);
    }
}
