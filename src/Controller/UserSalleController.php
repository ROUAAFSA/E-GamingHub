<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Entity\ReservationSalle;
use App\Form\ReservationSalleUserType;
use App\Repository\SalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/user/salles')]
class UserSalleController extends AbstractController
{
    #[Route('/', name: 'app_user_salle_index', methods: ['GET'])]
    public function index(SalleRepository $salleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search');
        $queryBuilder = $salleRepository->createQueryBuilder('s');

        if ($search) {
            $queryBuilder
                ->where('s.nom LIKE :search')
                ->orWhere('s.description LIKE :search')
                ->orWhere('s.capacite LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy('s.id', 'DESC');
        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('user_salle/index.html.twig', [
            'pagination' => $pagination,
            'show_pagination' => true
        ]);
    }

    #[Route('/{id}', name: 'app_user_salle_show', methods: ['GET', 'POST'])]
    public function show(Salle $salle, Request $request, EntityManagerInterface $entityManager): Response
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

        // Create a new reservation
        $reservation = new ReservationSalle();
        $reservation->setSalle($salle);
        $reservation->setUtilisateur($user);
        $reservation->setStatut('En attente');

        // Create the form
        $form = $this->createForm(ReservationSalleUserType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès et est en attente de confirmation.');
            return $this->redirectToRoute('app_user_reservation_index');
        }

        return $this->render('user_salle/show.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
        ]);
    }
}
