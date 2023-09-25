<?php

namespace App\Repository\Support;

use App\Entity\Support\Support;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Support|null find($id, $lockMode = null, $lockVersion = null)
 * @method Support|null findOneBy(array $criteria, array $orderBy = null)
 * @method Support[]    findAll()
 * @method Support[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Support::class);
    }

    public function findEnabledSupports($indexed = false)
    {
        $qb = ($indexed) ? $this->createQueryBuilder('s', 's.name') : $this->createQueryBuilder('s');
        $qb = $qb
        ->select('s')
        ->where('s.enabled = true')
        ->addOrderBy('s.position', 'ASC')
        ->addOrderBy('s.label', 'ASC');
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return Support[] Returns an array of Support objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Support
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
