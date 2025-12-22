<?php

namespace App\Repository;

use App\Entity\ReservationSalle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationSalle>
 *
 * @method ReservationSalle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationSalle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationSalle[]    findAll()
 * @method ReservationSalle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationSalleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationSalle::class);
    }

    public function save(ReservationSalle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReservationSalle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
