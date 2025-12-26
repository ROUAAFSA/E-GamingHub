<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commentaire')]
final class CommentaireController extends AbstractController
{
    private array $badWords = [
        'idiot', 'stupid', 'jerk', 'damn', 'hell', // Add more as needed
        'merde', 'con', 'salope', 'putain', 'connard' // French bad words
    ];

    #[Route('/search', name: 'app_commentaire_search', methods: ['GET'])]
    public function search(Request $request, CommentaireRepository $commentaireRepository): Response
    {
        $search = $request->query->get('search', '');
        $signaled = $request->query->has('signaled') ? filter_var($request->query->get('signaled'), FILTER_VALIDATE_BOOLEAN) : null;

        $commentaires = $commentaireRepository->findByFilters($search, $signaled);

        return $this->render('commentaire/_comments_list.html.twig', [
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/', name: 'app_commentaire_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $commentaires = $entityManager
            ->getRepository(Commentaire::class)
            ->findAll();

        return $this->render('commentaire/index.html.twig', [
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/back', name: 'app_commentaire_indexback', methods: ['GET'])]
    public function index_back(CommentaireRepository $commentaireRepository): Response
    {
        $commentaires = $commentaireRepository->findByFilters();

        return $this->render('commentaire/index_back.html.twig', [
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/new', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for bad words in contenu
            $contenu = $commentaire->getContenu();
            $contenuLower = strtolower($contenu);
            foreach ($this->badWords as $badWord) {
                if (stripos($contenuLower, $badWord) !== false) {
                    $this->addFlash('error', 'Votre commentaire contient des mots inappropriés. Veuillez modifier le contenu.');
                    return $this->render('commentaire/new.html.twig', [
                        'commentaire' => $commentaire,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_commentaire_index');
        }

        return $this->render('commentaire/new.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new/for-post/{postId}', name: 'app_commentaire_new_for_post', methods: ['GET', 'POST'])]
    public function newForPost(int $postId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found for ID ' . $postId);
        }

        $commentaire = new Commentaire();
        $commentaire->setPost($post); // Pre-fill the post field

        $form = $this->createForm(CommentaireType::class, $commentaire, [
            'post_field_enabled' => false, // Disable the post field in the form
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for bad words in contenu
            $contenu = $commentaire->getContenu();
            $contenuLower = strtolower($contenu);
            foreach ($this->badWords as $badWord) {
                if (stripos($contenuLower, $badWord) !== false) {
                    $this->addFlash('error', 'Votre commentaire contient des mots inappropriés. Veuillez modifier le contenu.');
                    return $this->render('commentaire/new.html.twig', [
                        'commentaire' => $commentaire,
                        'form' => $form->createView(),
                        'post' => $post,
                    ]);
                }
            }

            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('commentaire/new.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/{id}', name: 'app_commentaire_show', methods: ['GET'])]
    public function show(Commentaire $commentaire): Response
    {
        return $this->render('commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_commentaire_index');
        }

        return $this->render('commentaire/edit.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $token)) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commentaire_index');
    }
}