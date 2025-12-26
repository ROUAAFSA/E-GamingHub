<?php

namespace App\Controller\Admin;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Repository\ProduitRepository;
use App\Repository\CodePromoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/promotions')]
class PromotionController extends AbstractController
{
    #[Route('/', name: 'admin_promotion_index', methods: ['GET'])]
    public function index(
        PromotionRepository $promotionRepository, 
        ProduitRepository $produitRepository,
        CodePromoRepository $codePromoRepository
    ): Response
    {
        return $this->render('admin/promotions.html.twig', [
            'promotions_produits' => $promotionRepository->findAll(),
            'produits' => $produitRepository->findAll(),
            'codes_promo' => $codePromoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_promotion_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProduitRepository $produitRepository): Response
    {
        $produitId = $request->request->get('produit');
        $pourcentage = $request->request->get('pourcentage');
        $dateDebut = new \DateTime($request->request->get('dateDebut'));
        $dateFin = new \DateTime($request->request->get('dateFin'));

        $produit = $produitRepository->find($produitId);
        
        if (!$produit) {
            $this->addFlash('error', 'Produit non trouvé.');
            return $this->redirectToRoute('admin_promotion_index');
        }

        $promotion = new Promotion();
        $promotion->setProduit($produit)
                 ->setPourcentage($pourcentage)
                 ->setDateDebut($dateDebut)
                 ->setDateFin($dateFin)
                 ->setActif(true);

        $entityManager->persist($promotion);
        $entityManager->flush();

        $this->addFlash('success', 'La promotion a été créée avec succès.');
        return $this->redirectToRoute('admin_promotion_index');
    }

    #[Route('/{id}/edit', name: 'admin_promotion_edit', methods: ['POST'])]
    public function edit(Request $request, Promotion $promotion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('edit'.$promotion->getId(), $request->request->get('_token'))) {
            $pourcentage = $request->request->get('pourcentage');
            $dateDebut = new \DateTime($request->request->get('dateDebut'));
            $dateFin = new \DateTime($request->request->get('dateFin'));

            $promotion->setPourcentage($pourcentage)
                     ->setDateDebut($dateDebut)
                     ->setDateFin($dateFin);

            $entityManager->flush();

            $this->addFlash('success', 'La promotion a été mise à jour avec succès.');
        }

        return $this->redirectToRoute('admin_promotion_index');
    }

    #[Route('/{id}', name: 'admin_promotion_delete', methods: ['POST'])]
    public function delete(Request $request, Promotion $promotion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promotion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($promotion);
            $entityManager->flush();

            $this->addFlash('success', 'La promotion a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_promotion_index');
    }
} 