<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/post')]
final class EvenementController extends AbstractController
{
    #[Route(name: 'app_post_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $query = $entityManager
            ->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6 // Nombre d'éléments par page
        );

        return $this->render('post/index.html.twig', [
            'posts' => $pagination,
        ]);
    }

    #[Route('/search', name: 'app_post_search', methods: ['GET'])]
    public function search(Request $request, PostRepository $postRepository): Response
    {
        $filters = [
            'search' => $request->query->get('search', ''),
            'category' => $request->query->get('category', ''),
            'status' => $request->query->has('status') ? filter_var($request->query->get('status'), FILTER_VALIDATE_BOOLEAN) : null,
            'signaled' => $request->query->has('signaled') ? filter_var($request->query->get('signaled'), FILTER_VALIDATE_BOOLEAN) : null,
        ];

        // Remove null or empty filters
        $filters = array_filter($filters, fn($value) => !is_null($value) && $value !== '');

        $sortBy = $request->query->get('sortBy', 'updatedAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        // Validate sort parameters
        $validSortFields = ['likeCount', 'dislikeCount', 'updatedAt'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'updatedAt';
        $sortOrder = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) ? $sortOrder : 'DESC';

        $posts = $postRepository->findByFilters(
            $filters['search'] ?? null,
            $filters['category'] ?? null,
            $filters['status'] ?? null,
            $filters['signaled'] ?? null,
            $sortBy,
            $sortOrder
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('post/_posts_table.html.twig', [
                'posts' => $posts,
            ]);
        }

        return $this->redirectToRoute('app_post_indexback');
    }

    #[Route('/back', name: 'app_post_indexback', methods: ['GET'])]
    public function indexback(Request $request, PostRepository $postRepository, PaginatorInterface $paginator): Response
    {
        $query = $postRepository->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );

        $categories = $postRepository->createQueryBuilder('p')
            ->select('DISTINCT p.categorie')
            ->getQuery()
            ->getResult();
        $categories = array_column($categories, 'categorie');

        return $this->render('post/indexback.html.twig', [
            'posts' => $pagination,
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $post = new Post();
        $post->setUpdatedAt(new \DateTime());
        $post->setSignaled(false);
        $post->setEnable(true);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageName')->getData();

            if ($file) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('image_directory'),
                    $fileName
                );
                $post->setImageName($fileName);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            // Send Email Notification
            $email = (new Email())
                ->from('ferjaoui.baha@esprit.tn') // Replace with your sender email
                ->to('bahefarjeoui@gmail.com') // Replace with recipient email
                ->subject('🆕 Nouveau Post Créé')
                ->html("
                    <h2>📢 Nouveau Post Créé</h2>
                    <p><strong>📌 Titre :</strong> {$post->getTitre()}</p>
                    <p><strong>📝 Contenu :</strong> {$post->getDescription()}</p>
                    <p><strong>🏷 Catégorie :</strong> {$post->getCategorie()}</p>
                    <p><strong>📅 Date :</strong> {$post->getUpdatedAt()->format('Y-m-d H:i:s')}</p>
                    <p>Merci !</p>
                ");

            try {
                $mailer->send($email);
                $this->addFlash('success', '✔ Post ajouté et email envoyé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('danger', '❌ Échec de l\'envoi de l\'email: ' . $e->getMessage());
                error_log("❌ Mailer Error: " . $e->getMessage());
            }

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageName')->getData();

            if ($file) {
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move(
                    $this->getParameter('image_directory'),
                    $fileName
                );
                $post->setImageName($fileName);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/by-commentaire/{commentaireId}', name: 'app_post_by_commentaire', methods: ['GET'])]
    public function showByCommentaire(int $commentaireId, EntityManagerInterface $entityManager): Response
    {
        $commentaire = $entityManager
            ->getRepository(Commentaire::class)
            ->find($commentaireId);

        if (!$commentaire) {
            throw $this->createNotFoundException('Commentaire non trouvé pour l\'ID ' . $commentaireId);
        }

        $post = $commentaire->getPost();

        if (!$post) {
            throw $this->createNotFoundException('Aucun post associé au commentaire ID ' . $commentaireId);
        }

        return $this->render('post/show_comments_by_post.html.twig', [
            'post' => $post,
            'commentaire_id' => $commentaireId,
        ]);
    }

    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'])]
    public function like(Post $post, EntityManagerInterface $entityManager, Request $request, SessionInterface $session): JsonResponse
    {
        $likedPosts = $session->get('liked_posts', []);
        $dislikedPosts = $session->get('disliked_posts', []);
        $postId = $post->getId();

        if (!$this->isCsrfTokenValid('like' . $postId, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_BAD_REQUEST);
        }

        if (in_array($postId, $likedPosts)) {
            $likedPosts = array_diff($likedPosts, [$postId]);
            $post->setLikeCount(max(0, $post->getLikeCount() - 1));
            $session->set('liked_posts', $likedPosts);
        } else {
            if (in_array($postId, $dislikedPosts)) {
                $dislikedPosts = array_diff($dislikedPosts, [$postId]);
                $post->setDislikeCount(max(0, $post->getDislikeCount() - 1));
                $session->set('disliked_posts', $dislikedPosts);
            }
            $likedPosts[] = $postId;
            $post->setLikeCount($post->getLikeCount() + 1);
            $session->set('liked_posts', $likedPosts);
        }

        $entityManager->persist($post);
        $entityManager->flush();

        return new JsonResponse([
            'likeCount' => $post->getLikeCount(),
            'dislikeCount' => $post->getDislikeCount(),
            'hasLiked' => in_array($postId, $session->get('liked_posts', [])),
            'hasDisliked' => in_array($postId, $session->get('disliked_posts', [])),
        ]);
    }

    #[Route('/{id}/dislike', name: 'app_post_dislike', methods: ['POST'])]
    public function dislike(Post $post, EntityManagerInterface $entityManager, Request $request, SessionInterface $session): JsonResponse
    {
        $likedPosts = $session->get('liked_posts', []);
        $dislikedPosts = $session->get('disliked_posts', []);
        $postId = $post->getId();

        if (!$this->isCsrfTokenValid('dislike' . $postId, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_BAD_REQUEST);
        }

        if (in_array($postId, $dislikedPosts)) {
            $dislikedPosts = array_diff($dislikedPosts, [$postId]);
            $post->setDislikeCount(max(0, $post->getDislikeCount() - 1));
            $session->set('disliked_posts', $dislikedPosts);
        } else {
            if (in_array($postId, $likedPosts)) {
                $likedPosts = array_diff($likedPosts, [$postId]);
                $post->setLikeCount(max(0, $post->getLikeCount() - 1));
                $session->set('liked_posts', $likedPosts);
            }
            $dislikedPosts[] = $postId;
            $post->setDislikeCount($post->getDislikeCount() + 1);
            $session->set('disliked_posts', $dislikedPosts);
        }

        $entityManager->persist($post);
        $entityManager->flush();

        return new JsonResponse([
            'likeCount' => $post->getLikeCount(),
            'dislikeCount' => $post->getDislikeCount(),
            'hasLiked' => in_array($postId, $session->get('liked_posts', [])),
            'hasDisliked' => in_array($postId, $session->get('disliked_posts', [])),
        ]);
    }

    #[Route('/post/stats', name: 'app_post_stats')]
    public function stats(PostRepository $postRepository): Response
    {
        // Fetch data for charts
        $postsByCategory = $postRepository->getPostsByCategory();
        $commentsPerPost = $postRepository->getCommentsPerPost();
        $postsOverTime = $postRepository->getPostsOverTime();
        $signaledPostsCount = $postRepository->getSignaledPostsCount();
        $mostLikedPosts = $postRepository->getMostLikedPosts();
        $mostDislikedPosts = $postRepository->getMostDislikedPosts();

        return $this->render('post/stats.html.twig', [
            'postsByCategory' => $postsByCategory,
            'commentsPerPost' => $commentsPerPost,
            'postsOverTime' => $postsOverTime,
            'signaledPostsCount' => $signaledPostsCount,
            'mostLikedPosts' => $mostLikedPosts,
            'mostDislikedPosts' => $mostDislikedPosts,
        ]);
    }
}
