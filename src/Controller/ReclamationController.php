<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'app_reclamation_index')]
    public function index(ReclamationRepository $repository): Response
    {
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $repository->findAll(),
        ]);
    }

    #[Route('/ajouter', name: 'app_reclamation_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();  // statut and date set in constructor

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reclamation);
            $em->flush();

            $this->addFlash('success', 'Réclamation créée avec succès');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/new.html.twig', [
            'form' => $form,
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_reclamation_edit')]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Réclamation modifiée avec succès');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/edit.html.twig', [
            'form' => $form,
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $em->remove($reclamation);
        $em->flush();

        $this->addFlash('success', 'La réclamation a été supprimée avec succès.');
        return $this->redirectToRoute('app_reclamation_index');
    }
}
