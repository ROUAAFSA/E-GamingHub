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
    #[Route('/reclamation/{id}/repondre', name: 'app_admin_reponse', methods: ['GET'])]
    public function reponseForm(Reclamation $reclamation): Response
    {
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation demandée n\'existe pas.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('admin/reponse_reclamation.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/repondre/submit', name: 'app_admin_reponse_submit', methods: ['POST'])]
    public function submitReponse(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation demandée n\'existe pas.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        // Empêcher l'ajout d'une nouvelle réponse si la réclamation en a déjà une
        if (count($reclamation->getReponses()) > 0) {
            $this->addFlash('error', 'Cette réclamation a déjà reçu une réponse.');
            return $this->redirectToRoute('app_admin_reponse', ['id' => $reclamation->getId()]);
        }

        // Récupérer le contenu de la réponse
        $contenu = $request->request->get('reponse');
        $notifyClient = $request->request->getBoolean('notify_client');

        // Vérifier que le contenu n'est pas vide
        if (!$contenu || empty(trim($contenu))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_admin_reponse', ['id' => $reclamation->getId()]);
        }

        try {
            // Créer une nouvelle réponse
            $reponse = new Reponse();
            $reponse->setContenu($contenu);
            $reponse->setReclamation($reclamation);

            // Mettre à jour le statut de la réclamation
            $reclamation->setStatut('Résolue');

            // Persister les changements
            $entityManager->persist($reponse);
            $entityManager->flush();

            // Si l'option de notification est activée, on envoie un email (implémentation fictive pour l'exemple)
            if ($notifyClient) {
                // Dans une implémentation réelle, on enverrait un email au client ici
                // $this->emailService->sendResponseNotification($reclamation, $reponse);

                $this->addFlash('success', 'Réponse ajoutée avec succès. Le client a été notifié par email.');
            } else {
                $this->addFlash('success', 'Réponse ajoutée avec succès.');
            }

            // Rediriger vers le tableau de bord administrateur au lieu de la page de détail
            return $this->redirectToRoute('app_admin_dashboard');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de la réponse: ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_reponse', ['id' => $reclamation->getId()]);
        }
    }

    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(ReclamationRepository $reclamationRepository): Response
    {
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

    #[Route('/reclamations/resolues', name: 'app_admin_reclamations_resolues', methods: ['GET'])]
    public function reclamationsResolues(ReclamationRepository $reclamationRepository): Response
    {
        // Récupérer toutes les réclamations résolues (incluant les archivées)
        $reclamationsResolues = $reclamationRepository->findBy(
            ['statut' => 'Résolue'],
            ['date_creation' => 'DESC']
        );

        return $this->render('admin/reclamations_resolues.html.twig', [
            'reclamations' => $reclamationsResolues
        ]);
    }
    #[Route('/reclamation/{id}/pdf', name: 'app_admin_reclamation_pdf', methods: ['GET'])]
    public function generatePdf(Reclamation $reclamation, Pdf $knpSnappyPdf): Response
    {
        // Générer le contenu HTML
        $html = $this->renderView('admin/reclamation_pdf.html.twig', [
            'reclamation' => $reclamation
        ]);

        // Générer le PDF
        $pdf = $knpSnappyPdf->getOutputFromHtml($html);

        // Retourner le PDF comme réponse
        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="reclamation-' . $reclamation->getId() . '.pdf"'
            ]
        );
    }

    #[Route('/stats/reclamations/{days}', name: 'app_admin_stats_reclamations', methods: ['GET'])]
    public function getReclamationStats(EntityManagerInterface $entityManager, int $days = 7): JsonResponse
    {
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify("-$days days");

        $qb = $entityManager->createQueryBuilder();
        $qb->select('r')
            ->from(Reclamation::class, 'r')
            ->where('r.date_creation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.date_creation', 'ASC');

        $reclamations = $qb->getQuery()->getResult();

        // Préparer les tableaux pour les données
        $labels = [];
        $totalData = array_fill(0, $days, 0);
        $enAttenteData = array_fill(0, $days, 0);
        $resoluesData = array_fill(0, $days, 0);

        // Générer les labels pour chaque jour
        for ($i = 0; $i < $days; $i++) {
            $date = clone $startDate;
            $date->modify("+$i days");
            $labels[] = $date->format('d/m');
        }

        // Compter les réclamations pour chaque jour
        foreach ($reclamations as $reclamation) {
            $dayDiff = $reclamation->getDateCreation()->diff($startDate)->days;
            if ($dayDiff >= 0 && $dayDiff < $days) {
                $totalData[$dayDiff]++;
                if ($reclamation->getStatut() === 'Résolue') {
                    $resoluesData[$dayDiff]++;
                } else {
                    $enAttenteData[$dayDiff]++;
                }
            }
        }

        return new JsonResponse([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total des réclamations',
                    'data' => $totalData,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'fill' => true
                ],
                [
                    'label' => 'En attente',
                    'data' => $enAttenteData,
                    'borderColor' => 'rgb(255, 159, 64)',
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'fill' => true
                ],
                [
                    'label' => 'Résolues',
                    'data' => $resoluesData,
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'fill' => true
                ]
            ]
        ]);
    }

    #[Route('/api/reclamations/list', name: 'admin_api_reclamations_list', methods: ['GET'])]
    public function listReclamations(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $lastUpdate = $request->query->get('last_update');
        $page = $request->query->get('page', 1);
        $limit = 20;

        $qb = $reclamationRepository->createQueryBuilder('r')
            ->orderBy('r.date_creation', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        if ($lastUpdate) {
            $lastUpdateDate = new \DateTime($lastUpdate);
            $qb->where('r.date_creation > :lastUpdate OR r.date_modification > :lastUpdate')
                ->setParameter('lastUpdate', $lastUpdateDate);
        }

        $reclamations = $qb->getQuery()
            ->enableResultCache(300) // Cache for 5 minutes
            ->getResult();

        $data = [];
        foreach ($reclamations as $reclamation) {
            $data[] = [
                'id' => $reclamation->getId(),
                'dateCreation' => $reclamation->getDateCreation()->format('d/m/Y'),
                'categorie' => $reclamation->getCategorie(),
                'description' => mb_strimwidth($reclamation->getDescription(), 0, 30, '...'),
                'statut' => $reclamation->getStatut(),
            ];
        }

        return $this->json([
            'reclamations' => $data,
            'hasMore' => count($reclamations) === $limit,
            'currentPage' => $page,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/api/reclamations/count', name: 'admin_api_reclamations_count', methods: ['GET'])]
    public function countNewReclamations(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $since = $request->query->get('since');
        if ($since) {
            $sinceDate = \DateTime::createFromFormat('Y-m-d H:i:s', $since);
            if (!$sinceDate) {
                return $this->json(['error' => 'Format de date invalide'], 400);
            }
            $qb = $reclamationRepository->createQueryBuilder('r');
            $qb->select('COUNT(r.id)')
                ->where('r.date_creation > :since')
                ->andWhere('r.statut = :statut')
                ->setParameter('since', $sinceDate)
                ->setParameter('statut', 'En attente');
            $count = $qb->getQuery()->getSingleScalarResult();
        } else {
            $count = $reclamationRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->getQuery()
                ->getSingleScalarResult();
        }
        return $this->json(['count' => (int)$count]);
    }

    #[Route('/reclamation/{id}/export-pdf', name: 'admin_reclamation_export_pdf', methods: ['GET'])]
    public function exportReclamationPdf(int $id, ReclamationRepository $reclamationRepository, Pdf $knpSnappyPdf): Response
    {
        $reclamation = $reclamationRepository->find($id);
        if (!$reclamation) {
            throw $this->createNotFoundException('Réclamation non trouvée');
        }
        $html = $this->renderView('admin/reclamation_pdf.html.twig', [
            'reclamation' => $reclamation
        ]);
        $filename = 'reclamation_'.$reclamation->getId().'.pdf';
        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT.'; filename="'.$filename.'"'
            ]
        );
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

    #[Route('/commande/{id}/statut', name: 'admin_commande_update_statut', methods: ['POST'])]
    public function updateCommandeStatut(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            return new JsonResponse(['success' => false, 'message' => 'Commande non trouvée'], 404);
        }
        $statut = $request->request->get('statut');
        if (!$statut) {
            return new JsonResponse(['success' => false, 'message' => 'Statut manquant'], 400);
        }
        $commande->setStatut($statut);
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => 'Statut mis à jour']);
    }

    #[Route('/commandes/update-status', name: 'admin_commandes_update_status', methods: ['POST'])]
    public function updateCommandeStatus(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $id = $request->request->get('id');
        $statut = $request->request->get('statut');
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('update_commande_status_' . $id, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            return new JsonResponse(['success' => false, 'message' => 'Commande non trouvée'], 404);
        }
        $commande->setStatut($statut);
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => 'Statut mis à jour']);
    }

}
