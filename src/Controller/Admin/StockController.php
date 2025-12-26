<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Entity\MouvementStock;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stock')]
class StockController extends AbstractController
{
    #[Route('/', name: 'admin_stock_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findAll();
        $produitsEnAlerte = array_filter($produits, fn($p) => $p->estEnAlerte());

        return $this->render('admin/stock/index.html.twig', [
            'produits' => $produits,
            'produitsEnAlerte' => $produitsEnAlerte,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_stock_edit', methods: ['POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('edit'.$produit->getId(), $request->request->get('_token'))) {
            $stock = $request->request->getInt('stock');
            $seuilAlerte = $request->request->getInt('seuilAlerte');
            $commentaire = $request->request->get('commentaire');

            $ancienStock = $produit->getStock();
            $difference = $stock - $ancienStock;

            if ($difference != 0) {
                if ($difference > 0) {
                    $produit->ajouterStock($difference, 'ajustement', $commentaire);
                } else {
                    $produit->retirerStock(abs($difference), 'ajustement', $commentaire);
                }
            }

            $produit->setSeuilAlerte($seuilAlerte);
            $entityManager->flush();

            $this->addFlash('success', 'Le stock a été mis à jour avec succès.');
        }

        return $this->redirectToRoute('admin_stock_index');
    }

    #[Route('/{id}/reapprovisionner', name: 'admin_stock_reapprovisionner', methods: ['POST'])]
    public function reapprovisionner(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reappro'.$produit->getId(), $request->request->get('_token'))) {
            $quantite = $request->request->getInt('quantite');
            
            if ($quantite > 0) {
                $produit->ajouterStock($quantite, 'réapprovisionnement', 'Réapprovisionnement manuel');
                $entityManager->flush();
                
                $this->addFlash('success', sprintf('Le stock a été augmenté de %d unités.', $quantite));
            }
        }

        return $this->redirectToRoute('admin_stock_index');
    }

    #[Route('/{id}/mouvements', name: 'admin_stock_mouvements', methods: ['GET'])]
    public function mouvements(Produit $produit): Response
    {
        return $this->render('admin/stock/mouvements.html.twig', [
            'produit' => $produit,
            'mouvements' => $produit->getMouvementsStock(),
        ]);
    }
} 