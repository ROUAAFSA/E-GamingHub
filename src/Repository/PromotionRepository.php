<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 *
 * @method Promotion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promotion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promotion[]    findAll()
 * @method Promotion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    /**
     * @return Promotion[] Returns an array of active Promotion objects
     */
    public function findActives(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.actif = :actif')
            ->andWhere('p.dateDebut <= :now')
            ->andWhere('p.dateFin >= :now')
            ->setParameter('actif', true)
            ->setParameter('now', $now)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 