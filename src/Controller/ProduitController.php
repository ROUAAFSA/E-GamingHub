<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/produit')]
class ProduitController extends AbstractController
{

    #[Route('/', name: 'produit_liste')]
    public function liste(EntityManagerInterface $em): Response
    {
        $produits = $em->getRepository(Produit::class)->findAll();

        return $this->render('Produit/produit_liste.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/ajouter', name: 'produit_ajouter')]
    public function ajouterProduit(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('produit_liste');
        }

        return $this->render('Produit/produit_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false
        ]);
    }

    #[Route('/{id}/modifier', name: 'produit_modifier')]
    public function modifierProduit(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('produit_liste');
        }

        return $this->render('Produit/produit_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true
        ]);
    }

    #[Route('/{id}/supprimer', name: 'produit_supprimer', methods: ['POST'])]
    public function supprimerProduit(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('supprimer_produit_' . $produit->getId(), $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();

            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('produit_liste');
    }
}
