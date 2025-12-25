<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CodePromo>
 *
 * @method CodePromo|null find($id, $lockMode = null, $lockVersion = null)
 * @method CodePromo|null findOneBy(array $criteria, array $orderBy = null)
 * @method CodePromo[]    findAll()
 * @method CodePromo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

    /**
     * @return CodePromo[] Returns an array of active CodePromo objects
     */
    public function findActifs(): array
    {
        $maintenant = new \DateTime();
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.actif = :actif')
            ->andWhere('(c.dateExpiration IS NULL OR c.dateExpiration > :maintenant)')
            ->andWhere('(c.nbUtilisationsMax IS NULL OR c.nbUtilisations < c.nbUtilisationsMax)')
            ->setParameter('actif', true)
            ->setParameter('maintenant', $maintenant)
            ->orderBy('c.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 