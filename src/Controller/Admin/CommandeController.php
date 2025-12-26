<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Repository\CommandeRepository;
use App\Service\PDFService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commandes')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'admin_commande_index', methods: ['GET'])]
    public function index(Request $request, CommandeRepository $commandeRepository): Response
    {
        $statut = $request->query->get('statut');
        $client = $request->query->get('client');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        $queryBuilder = $commandeRepository->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')
            ->addSelect('u');

        if ($statut) {
            $queryBuilder->andWhere('c.statut = :statut')
                        ->setParameter('statut', $statut);
        }

        if ($client) {
            $queryBuilder->andWhere('u.email LIKE :client OR u.nom LIKE :client')
                        ->setParameter('client', '%' . $client . '%');
        }

        if ($dateDebut) {
            $queryBuilder->andWhere('c.date >= :dateDebut')
                        ->setParameter('dateDebut', new \DateTime($dateDebut));
        }

        if ($dateFin) {
            $queryBuilder->andWhere('c.date <= :dateFin')
                        ->setParameter('dateFin', new \DateTime($dateFin));
        }

        $commandes = $queryBuilder->orderBy('c.date', 'DESC')
                                ->getQuery()
                                ->getResult();

        return $this->render('admin/commande/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/{id}', name: 'admin_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('admin/commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_commande_edit', methods: ['POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('edit'.$commande->getId(), $request->request->get('_token'))) {
            $statut = $request->request->get('statut');
            $notes = $request->request->get('notes');

            $commande->setStatut($statut);
            if ($notes) {
                $commande->setNotesInternes($notes);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La commande a été mise à jour avec succès.');
        }

        return $this->redirectToRoute('admin_commande_show', ['id' => $commande->getId()]);
    }

    #[Route('/{id}/facture', name: 'admin_commande_facture', methods: ['GET'])]
    public function generateFacture(Commande $commande, PDFService $pdfService): Response
    {
        $html = $this->renderView('admin/commande/facture.html.twig', [
            'commande' => $commande,
        ]);

        $filename = sprintf('facture_%s.pdf', $commande->getId());

        return $pdfService->generatePDF($html, $filename);
    }
} 