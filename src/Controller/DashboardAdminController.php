<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/dashboard/admin')]
final class DashboardAdminController extends AbstractController
{
    /**
     * Check if user is admin, redirect if not
     */
    private function checkAdminAccess(): ?Response
    {
        $session = $this->container->get('request_stack')->getSession();
        $user = $session->get('user');

        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page');
            return $this->redirectToRoute('app_sign_in');
        }

        if ($user['role'] !== 'admin') {
            $this->addFlash('error', 'Accès refusé - Vous devez être administrateur');
            return $this->redirectToRoute('app_home');
        }

        return null;
    }

    #[Route('', name: 'app_dashboard_admin')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Check admin access
        if ($redirect = $this->checkAdminAccess()) {
            return $redirect;
        }

        // Get user statistics
        $repository = $entityManager->getRepository(Utilisateur::class);

        $joueurs = $repository->count(['role' => 'joueur']);
        $vendeurs = $repository->count(['role' => 'vendeur']);
        $organisateurs = $repository->count(['role' => 'organisateur']);
        $totalUtilisateurs = $joueurs + $vendeurs + $organisateurs;

        // Get all users except admins
        $utilisateurs = $repository->createQueryBuilder('u')
            ->where('u.role != :role')
            ->setParameter('role', 'admin')
            ->getQuery()
            ->getResult();

        return $this->render('dashboard_admin/index.html.twig', [
            'stats' => [
                'joueurs' => $joueurs,
                'vendeurs' => $vendeurs,
                'organisateurs' => $organisateurs,
                'total' => $totalUtilisateurs
            ],
            'utilisateurs' => $utilisateurs
        ]);
    }

    #[Route('/reclamations', name: 'app_admin_reclamations')]
    public function reclamations(ReclamationRepository $reclamationRepository): Response
    {
        // Check admin access
        if ($redirect = $this->checkAdminAccess()) {
            return $redirect;
        }

        // Get reclamation statistics
        $stats = [
            'total' => $reclamationRepository->count([]),
            'enAttente' => $reclamationRepository->count(['statut' => 'En attente']),
            'resolues' => $reclamationRepository->count(['statut' => 'Résolue'])
        ];

        // Get all reclamations
        $allReclamations = $reclamationRepository->findBy([], ['date_creation' => 'DESC']);
        $pendingReclamations = $reclamationRepository->findBy(
            ['statut' => 'En attente'],
            ['date_creation' => 'ASC']
        );

        // Generate graph data (you can customize this based on real data)
        $graphData = [
            (new \DateTime('-4 days'))->format('Y-m-d') => ['total' => 2, 'enAttente' => 1, 'resolues' => 1],
            (new \DateTime('-3 days'))->format('Y-m-d') => ['total' => 3, 'enAttente' => 2, 'resolues' => 1],
            (new \DateTime('-2 days'))->format('Y-m-d') => ['total' => 4, 'enAttente' => 1, 'resolues' => 3],
            (new \DateTime('-1 days'))->format('Y-m-d') => ['total' => 1, 'enAttente' => 0, 'resolues' => 1],
            (new \DateTime())->format('Y-m-d') => ['total' => 5, 'enAttente' => 1, 'resolues' => 4]
        ];

        return $this->render('admin/reclamations.html.twig', [
            'stats' => $stats,
            'allReclamations' => $allReclamations,
            'pendingReclamations' => $pendingReclamations,
            'graphData' => $graphData
        ]);
    }

    #[Route('/boutique', name: 'app_admin_boutique')]
    public function boutique(Request $request, EntityManagerInterface $em): Response
    {
        // Check admin access
        if ($redirect = $this->checkAdminAccess()) {
            return $redirect;
        }

        $section = $request->query->get('section', 'dashboard');
        $stats = [];
        $chartData = [];
        $produits = [];
        $commandes = [];
        $codes_promo = [];
        $promotions_produits = [];

        if ($section === 'dashboard') {
            // Product statistics
            $totalProduits = $em->getRepository(Produit::class)->count([]);
            $commandeRepo = $em->getRepository(Commande::class);
            $totalCommandes = $commandeRepo->count([]);

            // Orders by status
            $commandesParStatut = $commandeRepo->createQueryBuilder('c')
                ->select('c.statut, COUNT(c.id) as nb')
                ->where('c.statut != :stripe')
                ->setParameter('stripe', 'Payée en ligne')
                ->groupBy('c.statut')
                ->getQuery()->getResult();

            // Payment methods
            $nbPaiementStripe = $commandeRepo->count(['statut' => 'Payée en ligne']);
            $nbPaiementCash = $commandeRepo->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.statut != :stripe')
                ->setParameter('stripe', 'Payée en ligne')
                ->getQuery()->getSingleScalarResult();

            // Total sales
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

    #[Route('/toggle-block/{id}', name: 'app_toggle_block_user', methods: ['POST'])]
    public function toggleBlockUser(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Check if user is admin
            $session = $request->getSession();
            $user = $session->get('user');

            if (!$user || $user['role'] !== 'admin') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Accès refusé - Vous devez être administrateur'
                ], 403);
            }

            $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);

            if (!$utilisateur) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Cannot block admin
            if ($utilisateur->getRole() === 'admin') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Impossible de bloquer un administrateur'
                ], 403);
            }

            // Toggle block status
            $newStatus = !$utilisateur->isBlocked();
            $utilisateur->setIsBlocked($newStatus);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'isBlocked' => $newStatus,
                'message' => $newStatus ?
                    'L\'utilisateur a été bloqué avec succès' :
                    'L\'utilisateur a été débloqué avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/search', name: 'app_dashboard_admin_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $searchTerm = $request->query->get('q');

        if (!$searchTerm) {
            return new JsonResponse(['html' => '']);
        }

        $repository = $entityManager->getRepository(Utilisateur::class);
        $users = $repository->createQueryBuilder('u')
            ->where('u.prenom LIKE :term')
            ->orWhere('u.nom LIKE :term')
            ->orWhere('u.email LIKE :term')
            ->andWhere('u.role != :admin')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->setParameter('admin', 'admin')
            ->getQuery()
            ->getResult();

        $html = $this->renderView('dashboard_admin/_users_list.html.twig', [
            'utilisateurs' => $users
        ]);

        return new JsonResponse(['html' => $html]);
    }

    #[Route('/chart', name: 'app_dashboard_admin_chart', methods: ['GET'])]
    public function generateChart(EntityManagerInterface $entityManager): Response
    {
        // Get statistics
        $repository = $entityManager->getRepository(Utilisateur::class);
        $joueurs = $repository->count(['role' => 'joueur']);
        $vendeurs = $repository->count(['role' => 'vendeur']);
        $organisateurs = $repository->count(['role' => 'organisateur']);

        $html = $this->renderView('dashboard_admin/chart.html.twig', [
            'joueurs' => $joueurs,
            'vendeurs' => $vendeurs,
            'organisateurs' => $organisateurs
        ]);

        return new Response($html);
    }
}
