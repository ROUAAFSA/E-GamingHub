<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Form\ProduitType;
use App\Entity\Commande;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(ReclamationRepository $reclamationRepository): Response
    {
        $session = $this->container->get('request_stack')->getSession();
        $user = $session->get('user');

        // Statistiques pour le tableau de bord (incluant toutes les réclamations)
        $stats = [
            'total' => $reclamationRepository->count([]),
            'enAttente' => $reclamationRepository->count(['statut' => 'En attente']),
            'resolues' => $reclamationRepository->count(['statut' => 'Résolue'])
        ];

        // Récupérer toutes les réclamations (ordre décroissant)
        $allReclamations = $reclamationRepository->findBy([], ['date_creation' => 'DESC']);

        // Récupérer les réclamations en attente (toutes)
        $pendingReclamations = $reclamationRepository->findBy(['statut' => 'En attente'], ['date_creation' => 'ASC']);

        // Générer des données factices pour le graphe (à remplacer par vos vraies données)
        $graphData = [
            (new \DateTime('-4 days'))->format('Y-m-d') => ['total' => 2, 'enAttente' => 1, 'resolues' => 1],
            (new \DateTime('-3 days'))->format('Y-m-d') => ['total' => 3, 'enAttente' => 2, 'resolues' => 1],
            (new \DateTime('-2 days'))->format('Y-m-d') => ['total' => 4, 'enAttente' => 1, 'resolues' => 3],
            (new \DateTime('-1 days'))->format('Y-m-d') => ['total' => 1, 'enAttente' => 0, 'resolues' => 1],
            (new \DateTime())->format('Y-m-d') => ['total' => 5, 'enAttente' => 1, 'resolues' => 4]
        ];
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'allReclamations' => $allReclamations,
            'pendingReclamations' => $pendingReclamations,
            'graphData' => $graphData
        ]);
    }



    #[Route('/boutique', name: 'admin_boutique')]
    public function boutique(Request $request, EntityManagerInterface $em): Response
    {
        $section = $request->query->get('section', 'produits');
        $stats = [];
        $chartData = [];
        $produits = [];
        $commandes = [];
        $codes_promo = [];
        $promotions_produits = [];

        if ($section === 'dashboard') {
            // Statistiques produits
            $totalProduits = $em->getRepository(Produit::class)->count([]);
            $commandeRepo = $em->getRepository(Commande::class);
            $totalCommandes = $commandeRepo->count([]);
            // Commandes par statut (bar chart)
            $commandesParStatut = $commandeRepo->createQueryBuilder('c')
                ->select('c.statut, COUNT(c.id) as nb')
                ->where('c.statut != :stripe')
                ->setParameter('stripe', 'Payée en ligne')
                ->groupBy('c.statut')
                ->getQuery()->getResult();
            // Commandes par méthode de paiement (pie chart)
            $nbPaiementStripe = $commandeRepo->count(['statut' => 'Payée en ligne']);
            $nbPaiementCash = $commandeRepo->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.statut != :stripe')
                ->setParameter('stripe', 'Payée en ligne')
                ->getQuery()->getSingleScalarResult();
            // Recette totale
            $totalVentes = $commandeRepo->createQueryBuilder('c')
                ->select('SUM(c.montant)')
                ->getQuery()->getSingleScalarResult() ?? 0;
            $stats = [
                'totalProduits' => $totalProduits,
                'totalCommandes' => $totalCommandes,
                'totalVentes' => $totalVentes,
                'nbPaiementCash' => $nbPaiementCash,
                'nbPaiementStripe' => $nbPaiementStripe,
            ];
            $chartData = [
                'labels' => array_column($commandesParStatut, 'statut'),
                'data' => array_column($commandesParStatut, 'nb')
            ];
        }
        if ($section === 'produits') {
            $produits = $em->getRepository(Produit::class)->findAll();
        }
        if ($section === 'commandes') {
            $commandes = $em->getRepository(Commande::class)->findAll();
        }
        if ($section === 'promotions') {
            $codes_promo = $em->getRepository(\App\Entity\CodePromo::class)->findAll();
            $promotions_produits = $em->getRepository(\App\Entity\Promotion::class)->findAll();
            $produits = $em->getRepository(Produit::class)->findAll();
        }

        return $this->render('admin/boutique.html.twig', [
            'section' => $section,
            'stats' => $stats,
            'chartData' => $chartData,
            'produits' => $produits,
            'commandes' => $commandes,
            'codes_promo' => $codes_promo,
            'promotions_produits' => $promotions_produits,
        ]);
    }

    #[Route('/boutique/produit/ajouter', name: 'admin_produit_ajouter')]
    public function ajouterProduit(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('admin_boutique', ['section' => 'produits']);
        }
        return $this->render('admin/produit_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false
        ]);
    }

    #[Route('/boutique/produit/{id}/modifier', name: 'admin_produit_modifier')]
    public function modifierProduit(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('admin_boutique', ['section' => 'produits']);
        }
        return $this->render('admin/produit_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true
        ]);
    }

    #[Route('/boutique/produit/{id}/supprimer', name: 'admin_produit_supprimer', methods: ['POST'])]
    public function supprimerProduit(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('supprimer_produit_' . $produit->getId(), $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        }
        return $this->redirectToRoute('admin_boutique', ['section' => 'produits']);
    }




}
