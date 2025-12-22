<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Entity\Utilisateur;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Snappy\Pdf;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/boutique', name: 'boutique_client')]
    public function boutiqueClient(Request $request, EntityManagerInterface $em): Response
    {
        $section = $request->query->get('section', 'produits');
        $produits = [];
        $commandes = [];
        if ($section === 'produits') {
            $produits = $em->getRepository(Produit::class)->findAll();
        }
        if ($section === 'commandes') {
            $user = $request->getSession()->get('user');
            if ($user) {
                $commandes = $em->getRepository(Commande::class)->findBy(['utilisateur' => $user['id']]);
            }
        }
        return $this->render('client/boutique.html.twig', [
            'section' => $section,
            'produits' => $produits,
            'commandes' => $commandes,
        ]);
    }

    #[Route('/boutique/ajouter-au-panier/{id}', name: 'ajouter_au_panier', methods: ['POST'])]
    public function ajouterAuPanier(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        // Ajouter le produit (ou incrémenter la quantité)
        if (isset($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }
        $session->set('panier', $panier);
        $this->addFlash('success', 'Produit ajouté au panier !');
        return $this->redirectToRoute('boutique_client', ['section' => 'produits']);
    }

    #[Route('/panier', name: 'panier_client')]
    public function panier(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        $produits = [];
        $total = 0;
        if ($panier) {
            $produits = $em->getRepository(Produit::class)->findBy(['id' => array_keys($panier)]);
            foreach ($produits as $produit) {
                $total += $produit->getPrix() * $panier[$produit->getId()];
            }
        }
        return $this->render('client/panier.html.twig', [
            'panier' => $panier,
            'produits' => $produits,
            'total' => $total,
        ]);
    }

    #[Route('/panier/retirer/{id}', name: 'retirer_du_panier', methods: ['POST'])]
    public function retirerDuPanier(int $id, Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        if (isset($panier[$id])) {
            unset($panier[$id]);
            $session->set('panier', $panier);
            $this->addFlash('success', 'Produit retiré du panier.');
        }
        return $this->redirectToRoute('panier_client');
    }

    #[Route('/panier/modifier/{id}', name: 'modifier_quantite_panier', methods: ['POST'])]
    public function modifierQuantitePanier(int $id, Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        $quantite = max(1, (int)$request->request->get('quantite', 1));
        if (isset($panier[$id])) {
            $panier[$id] = $quantite;
            $session->set('panier', $panier);
            $this->addFlash('success', 'Quantité modifiée.');
        }
        return $this->redirectToRoute('panier_client');
    }

    #[Route('/panier/valider', name: 'valider_commande', methods: ['POST'])]
    public function validerCommande(Request $request, EntityManagerInterface $em, Pdf $knpSnappyPdf): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        $user = $session->get('user');

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour valider une commande.');
            return $this->redirectToRoute('panier_client');
        }

        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_client');
        }

        $utilisateur = $em->getRepository(Utilisateur::class)->find($user['id']);
        $commandes = [];
        foreach ($panier as $produitId => $quantite) {
            $produit = $em->getRepository(Produit::class)->find($produitId);
            if ($produit) {
                if ($produit->getStock() < $quantite) {
                    $this->addFlash('error', 'Stock insuffisant pour le produit ' . $produit->getNom() . '.');
                    return $this->redirectToRoute('panier_client');
                }
                $commande = new Commande();
                $commande->setDate(new \DateTime());
                $commande->setMontant($produit->getPrix() * $quantite);
                $commande->setStatut('En attente');
                $commande->setUtilisateur($utilisateur);
                $commande->setProduit($produit);
                $em->persist($commande);
                // Décrémenter le stock
                $produit->setStock($produit->getStock() - $quantite);
                $commandes[] = $commande;
            }
        }

        $em->flush();
        $session->remove('panier');
        // Génération du PDF de reçu avec KnpSnappy même pour paiement cash
        $html = $this->renderView('client/recu_commande.html.twig', [
            'utilisateur' => $utilisateur,
            'commandes' => $commandes,
            'date' => new \DateTime(),
            'paiement' => 'Cash',
        ]);
        $pdfFileName = 'recu_commande_' . $utilisateur->getId() . '_' . date('Ymd_His') . '.pdf';
        $recuDir = $this->getParameter('kernel.project_dir') . '/public/mes_reçus/';
        if (!is_dir($recuDir)) {
            mkdir($recuDir, 0777, true);
        }
        $pdfOutput = $knpSnappyPdf->getOutputFromHtml($html, [
            'enable-local-file-access' => true,
            'encoding' => 'UTF-8',
            'page-size' => 'A4',
        ]);
        file_put_contents($recuDir . $pdfFileName, $pdfOutput);
        // Afficher une page de confirmation avec lien de téléchargement
        return $this->render('client/confirmation_commande.html.twig', [
            'pdfFileName' => $pdfFileName
        ]);
    }

    #[Route('/panier/paiement-stripe', name: 'paiement_stripe')]
    public function paiementStripe(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_client');
        }
        $produits = $em->getRepository(Produit::class)->findBy(['id' => array_keys($panier)]);
        $stripeProducts = [];
        foreach ($produits as $produit) {
            $stripeProducts[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $produit->getNom(),
                    ],
                    'unit_amount' => intval($produit->getPrix() * 100),
                ],
                'quantity' => $panier[$produit->getId()],
            ];
        }
        $checkoutSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $stripeProducts,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('stripe_success', [], 0),
            'cancel_url' => $this->generateUrl('stripe_cancel', [], 0),
        ]);
        return $this->redirect($checkoutSession->url);
    }

    #[Route('/panier/stripe-success', name: 'stripe_success')]
    public function stripeSuccess(Request $request, EntityManagerInterface $em, Pdf $knpSnappyPdf): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        $user = $session->get('user');
        if (!$user || empty($panier)) {
            $this->addFlash('error', 'Session expirée ou panier vide.');
            return $this->redirectToRoute('panier_client');
        }
        $utilisateur = $em->getRepository(Utilisateur::class)->find($user['id']);
        $commandes = [];
        foreach ($panier as $produitId => $quantite) {
            $produit = $em->getRepository(Produit::class)->find($produitId);
            if ($produit) {
                if ($produit->getStock() < $quantite) {
                    $this->addFlash('error', 'Stock insuffisant pour le produit ' . $produit->getNom() . '.');
                    return $this->redirectToRoute('panier_client');
                }
                $commande = new Commande();
                $commande->setDate(new \DateTime());
                $commande->setMontant($produit->getPrix() * $quantite);
                $commande->setStatut('Payée en ligne');
                $commande->setUtilisateur($utilisateur);
                $commande->setProduit($produit);
                $em->persist($commande);
                // Décrémenter le stock
                $produit->setStock($produit->getStock() - $quantite);
                $commandes[] = $commande;
            }
        }
        $em->flush();
        $session->remove('panier');
        // Génération du PDF de reçu avec KnpSnappy
        $html = $this->renderView('client/recu_commande.html.twig', [
            'utilisateur' => $utilisateur,
            'commandes' => $commandes,
            'date' => new \DateTime(),
            'paiement' => 'Stripe',
        ]);
        $pdfFileName = 'recu_commande_' . $utilisateur->getId() . '_' . date('Ymd_His') . '.pdf';
        $recuDir = $this->getParameter('kernel.project_dir') . '/public/mes_reçus/';
        if (!is_dir($recuDir)) {
            mkdir($recuDir, 0777, true);
        }
        $pdfOutput = $knpSnappyPdf->getOutputFromHtml($html, [
            'enable-local-file-access' => true,
            'encoding' => 'UTF-8',
            'page-size' => 'A4',
        ]);
        file_put_contents($recuDir . $pdfFileName, $pdfOutput);
        // Afficher une page de confirmation avec lien de téléchargement
        return $this->render('client/confirmation_commande.html.twig', [
            'pdfFileName' => $pdfFileName
        ]);
    }

    #[Route('/panier/telecharger-recu', name: 'telecharger_recu')]
    public function telechargerRecu(Request $request): Response
    {
        $session = $request->getSession();
        $pdfOutput = $session->get('pdf_recu');
        $pdfFileName = $session->get('pdf_recu_name', 'recu_commande.pdf');
        if (!$pdfOutput) {
            throw $this->createNotFoundException('Aucun reçu à télécharger.');
        }
        $session->remove('pdf_recu');
        $session->remove('pdf_recu_name');
        return new Response(base64_decode($pdfOutput), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdfFileName . '"',
        ]);
    }

    #[Route('/panier/stripe-cancel', name: 'stripe_cancel')]
    public function stripeCancel(): Response
    {
        $this->addFlash('error', 'Le paiement a été annulé.');
        return $this->redirectToRoute('panier_client');
    }
}
