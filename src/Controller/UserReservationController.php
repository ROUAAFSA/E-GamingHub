<?php

namespace App\Controller;

use App\Entity\ReservationSalle;
use App\Repository\ReservationSalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/user/reservations')]
class UserReservationController extends AbstractController
{
    #[Route('/', name: 'app_user_reservation_index', methods: ['GET'])]
    public function index(ReservationSalleRepository $reservationRepository, PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get user from session
        $user = $this->getUser();
        if (!$user) {
            $userData = $request->getSession()->get('user');
            if (!$userData) {
                return $this->redirectToRoute('app_login');
            }
            $user = $entityManager->getRepository('App\Entity\Utilisateur')->find($userData['id']);
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }
        }

        $status = $request->query->get('status');

        $queryBuilder = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.salle', 's')
            ->addSelect('s')
            ->where('r.utilisateur = :user')
            ->setParameter('user', $user);

        if ($status) {
            $queryBuilder
                ->andWhere('r.statut = :status')
                ->setParameter('status', $status);
        }

        $queryBuilder->orderBy('r.dateDebut', 'DESC');
        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('user_reservation/index.html.twig', [
            'pagination' => $pagination,
            'show_pagination' => true
        ]);
    }

    #[Route('/{id}', name: 'app_user_reservation_show', methods: ['GET'])]
    public function show(ReservationSalle $reservation, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get user from session
        $user = $this->getUser();
        if (!$user) {
            $userData = $request->getSession()->get('user');
            if (!$userData) {
                return $this->redirectToRoute('app_login');
            }
            $user = $entityManager->getRepository('App\Entity\Utilisateur')->find($userData['id']);
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }
        }

        // Security check: only allow users to view their own reservations
        if ($reservation->getUtilisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette réservation.');
        }

        return $this->render('user_reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_user_reservation_cancel', methods: ['POST'])]
    public function cancel(ReservationSalle $reservation, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get user from session
        $user = $this->getUser();
        if (!$user) {
            $userData = $request->getSession()->get('user');
            if (!$userData) {
                return $this->redirectToRoute('app_login');
            }
            $user = $entityManager->getRepository('App\Entity\Utilisateur')->find($userData['id']);
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }
        }

        // Security check: only allow users to cancel their own reservations
        if ($reservation->getUtilisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette réservation.');
        }

        // Check CSRF token
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            // Only allow cancellation if the reservation is not already cancelled
            if ($reservation->getStatut() !== 'Annulée') {
                $reservation->setStatut('Annulée');
                $entityManager->flush();
                $this->addFlash('success', 'La réservation a été annulée avec succès.');
            } else {
                $this->addFlash('warning', 'Cette réservation est déjà annulée.');
            }
        }

        return $this->redirectToRoute('app_user_reservation_index');
    }
}
