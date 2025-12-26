<?php

namespace App\Controller\Admin;

use App\Entity\CodePromo;
use App\Form\CodePromoType;
use App\Repository\CodePromoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/codes-promo')]
class CodePromoController extends AbstractController
{
    #[Route('/', name: 'admin_code_promo_index', methods: ['GET'])]
    public function index(CodePromoRepository $codePromoRepository): Response
    {
        return $this->render('admin/code_promo/index.html.twig', [
            'codes_promo' => $codePromoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_code_promo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $codePromo = new CodePromo();
        $form = $this->createForm(CodePromoType::class, $codePromo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($codePromo);
            $entityManager->flush();

            $this->addFlash('success', 'Le code promo a été créé avec succès.');
            return $this->redirectToRoute('admin_code_promo_index');
        }

        return $this->render('admin/code_promo/new.html.twig', [
            'code_promo' => $codePromo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_code_promo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CodePromo $codePromo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CodePromoType::class, $codePromo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le code promo a été mis à jour avec succès.');
            return $this->redirectToRoute('admin_code_promo_index');
        }

        return $this->render('admin/code_promo/edit.html.twig', [
            'code_promo' => $codePromo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_code_promo_delete', methods: ['POST'])]
    public function delete(Request $request, CodePromo $codePromo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$codePromo->getId(), $request->request->get('_token'))) {
            $entityManager->remove($codePromo);
            $entityManager->flush();

            $this->addFlash('success', 'Le code promo a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_code_promo_index');
    }

    #[Route('/{id}/toggle', name: 'admin_code_promo_toggle', methods: ['POST'])]
    public function toggle(Request $request, CodePromo $codePromo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$codePromo->getId(), $request->request->get('_token'))) {
            $codePromo->setActif(!$codePromo->isActif());
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le code promo a été %s avec succès.',
                $codePromo->isActif() ? 'activé' : 'désactivé'
            ));
        }

        return $this->redirectToRoute('admin_code_promo_index');
    }
} 