<?php

namespace App\Repository;

use App\Entity\Maschine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Maschine|null find($id, $lockMode = null, $lockVersion = null)
 * @method Maschine|null findOneBy(array $criteria, array $orderBy = null)
 * @method Maschine[]    findAll()
 * @method Maschine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaschineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maschine::class);
    }

    // /**
    //  * @return Maschine[] Returns an array of Maschine objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Maschine
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
