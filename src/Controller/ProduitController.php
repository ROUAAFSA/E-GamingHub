<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/produit')]
class ProduitController extends AbstractController
{

    //get a list of products
    #[Route('/', name: 'produit_liste')]
    public function liste(EntityManagerInterface $em): Response
    {
        $produits = $em->getRepository(Produit::class)->findAll();

        return $this->render('Produit/produit_liste.html.twig', [
            'produits' => $produits,
        ]);
    }

    //add a new product
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
            'form' => $form->createView()]);
    }

    //modify a product
    #[Route('/{id}/modifier', name: 'produit_modifier')]
    public function modifierProduit(Request $request,$id ,ProduitRepository $pr, EntityManagerInterface $em): Response
    {
        $produit = $pr->find($id);
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('produit_liste');
        }

        return $this->render('Produit/produit_form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true
        ]);
    }

    //delete a product
    #[Route('/{id}/supprimer', name: 'produit_supprimer')]
    public function supprimerProduit(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
            $em->remove($produit);
            $em->flush();

            $this->addFlash('success', 'Produit supprimé avec succès.');

        return $this->redirectToRoute('produit_liste');
    }
}
