<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Form\SalleType;
use App\Repository\SalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/salle')]
class SalleController extends AbstractController
{
    #[Route('/', name: 'app_salle_index', methods: ['GET'])]
    public function index(SalleRepository $salleRepository, PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
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
            4
        );

        // Calcul de la capacité moyenne des salles
        $avgCapacityResult = $salleRepository->createQueryBuilder('s')
            ->select('AVG(s.capacite) as avgCapacity')
            ->getQuery()
            ->getSingleResult();
        $avgCapacity = round($avgCapacityResult['avgCapacity'] ?? 0);

        // Récupération du nombre de réservations actives
        $activeReservations = 0;
        if (class_exists('App\Repository\ReservationSalleRepository')) {
            $reservationRepo = $entityManager->getRepository('App\Entity\ReservationSalle');
            if (method_exists($reservationRepo, 'createQueryBuilder')) {
                $activeReservations = $reservationRepo->createQueryBuilder('r')
                    ->select('COUNT(r.id)')
                    ->where('r.statut = :statut')
                    ->andWhere('r.dateFin >= :now')
                    ->setParameter('statut', 'Confirmée')
                    ->setParameter('now', new \DateTime())
                    ->getQuery()
                    ->getSingleScalarResult();
            }
        }

        return $this->render('salle/dashboard.html.twig', [
            'pagination' => $pagination,
            'salles' => $salleRepository->findAll(),
            'show_pagination' => true,
            'avg_capacity' => $avgCapacity,
            'active_reservations' => $activeReservations,
            'stats' => [
                'total' => count($salleRepository->findAll()),
                'avgCapacity' => $avgCapacity,
                'available' => count($salleRepository->findAll()) - $activeReservations,
                'reservations' => $activeReservations,
                'smallRooms' => $salleRepository->createQueryBuilder('s')
                    ->select('COUNT(s.id)')
                    ->where('s.capacite < 10')
                    ->getQuery()
                    ->getSingleScalarResult(),
                'mediumRooms' => $salleRepository->createQueryBuilder('s')
                    ->select('COUNT(s.id)')
                    ->where('s.capacite >= 10')
                    ->andWhere('s.capacite < 30')
                    ->getQuery()
                    ->getSingleScalarResult(),
                'largeRooms' => $salleRepository->createQueryBuilder('s')
                    ->select('COUNT(s.id)')
                    ->where('s.capacite >= 30')
                    ->andWhere('s.capacite < 50')
                    ->getQuery()
                    ->getSingleScalarResult(),
                'extraLargeRooms' => $salleRepository->createQueryBuilder('s')
                    ->select('COUNT(s.id)')
                    ->where('s.capacite >= 50')
                    ->getQuery()
                    ->getSingleScalarResult()
            ]
        ]);
    }

    #[Route('/new', name: 'app_salle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salle = new Salle();
        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salle);
            $entityManager->flush();

            $this->addFlash('success', 'La salle a été créée avec succès.');
            return $this->redirectToRoute('app_salle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salle/new.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_salle_show', methods: ['GET'])]
    public function show(EntityManagerInterface $entityManager, $id): Response
    {
        $salle = $entityManager->getRepository(Salle::class)->find($id);
        
        if (!$salle) {
            throw $this->createNotFoundException('La salle demandée n\'existe pas.');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_salle_delete', ['id' => $salle->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('salle/show.html.twig', [
            'salle' => $salle,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_salle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        $salle = $entityManager->getRepository(Salle::class)->find($id);
        
        if (!$salle) {
            throw $this->createNotFoundException('La salle demandée n\'existe pas.');
        }

        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_salle_delete', ['id' => $salle->getId()]))
            ->setMethod('POST')
            ->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La salle a été modifiée avec succès.');
            return $this->redirectToRoute('app_salle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salle/edit.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_salle_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, mixed $id): Response
    {
        $salle = $entityManager->getRepository(Salle::class)->find($id);
        
        if (!$salle) {
            throw $this->createNotFoundException('La salle demandée n\'existe pas.');
        }

        if ($this->isCsrfTokenValid('delete'.$salle->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($salle);
                $entityManager->flush();
                $this->addFlash('success', 'La salle a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer la salle car elle est liée à des réservations.');
                return $this->redirectToRoute('app_salle_show', ['id' => $id]);
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_salle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/dashboard', name: 'app_salle_dashboard', methods: ['GET'])]
    public function dashboard(SalleRepository $salleRepository, EntityManagerInterface $entityManager): Response
    {
        $salles = $salleRepository->findAll();
        
        // Calculate statistics
        $total = count($salles);
        
        // Calculate average capacity
        $totalCapacity = 0;
        $smallRooms = 0;
        $mediumRooms = 0;
        $largeRooms = 0;
        $extraLargeRooms = 0;
        
        foreach ($salles as $salle) {
            $totalCapacity += $salle->getCapacite();
            
            // Categorize rooms by capacity
            $capacity = $salle->getCapacite();
            if ($capacity < 10) {
                $smallRooms++;
            } elseif ($capacity >= 10 && $capacity < 30) {
                $mediumRooms++;
            } elseif ($capacity >= 30 && $capacity < 50) {
                $largeRooms++;
            } else {
                $extraLargeRooms++;
            }
        }
        
        $avgCapacity = $total > 0 ? $totalCapacity / $total : 0;
        
        // Count available rooms (not reserved today)
        $now = new \DateTime();
        $today = new \DateTime($now->format('Y-m-d'));
        $tomorrow = new \DateTime($now->format('Y-m-d'));
        $tomorrow->modify('+1 day');
        
        $reservationRepo = $entityManager->getRepository('App\Entity\ReservationSalle');
        $reservedRoomIds = [];
        
        if (method_exists($reservationRepo, 'createQueryBuilder')) {
            $reservations = $reservationRepo->createQueryBuilder('r')
                ->select('IDENTITY(r.salle)')
                ->where('r.statut = :statut')
                ->andWhere('r.dateDebut < :tomorrow')
                ->andWhere('r.dateFin > :today')
                ->setParameter('statut', 'Confirmée')
                ->setParameter('tomorrow', $tomorrow)
                ->setParameter('today', $today)
                ->getQuery()
                ->getResult();
            
            foreach ($reservations as $reservation) {
                $reservedRoomIds[] = $reservation[1];
            }
        }
        
        $available = $total - count(array_unique($reservedRoomIds));
        
        // Count total reservations
        $totalReservations = 0;
        if (method_exists($reservationRepo, 'createQueryBuilder')) {
            $totalReservations = $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        $stats = [
            'total' => $total,
            'avgCapacity' => $avgCapacity,
            'available' => $available,
            'reservations' => $totalReservations,
            'smallRooms' => $smallRooms,
            'mediumRooms' => $mediumRooms,
            'largeRooms' => $largeRooms,
            'extraLargeRooms' => $extraLargeRooms
        ];
        
        return $this->render('salle/dashboard.html.twig', [
            'salles' => $salles,
            'stats' => $stats
        ]);
    }

    #[Route('/export/pdf', name: 'app_salle_export_pdf', methods: ['GET'])]
    public function exportPdf(SalleRepository $salleRepository): Response
    {
        // Get all salles
        $salles = $salleRepository->findAll();
        
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        // Instantiate Dompdf
        $dompdf = new Dompdf($options);
        
        // Generate HTML content
        $html = $this->renderView('salle/pdf.html.twig', [
            'salles' => $salles,
            'date' => new \DateTime(),
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the PDF
        $dompdf->render();
        
        // Generate a filename
        $filename = 'liste_salles_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Return the PDF as response
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }
}
