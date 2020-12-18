<?php

namespace App\Repository;

use App\Entity\Filter;
use App\Entity\Subscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subscriber|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscriber|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscriber[]    findAll()
 * @method Subscriber[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscriber::class);
    }

    /**
     * @param Filter $filter
     * @return int|mixed|string
     */
    public function findByFilter(Filter $filter)
    {
        $queryBuilder = $this->createQueryBuilder('sr');
        if (!empty($filter->getFromSeason()) && !empty($filter->getToSeason())) {
            $queryBuilder = $queryBuilder->join('sr.subscriptions', 'sn')
            ->join('sn.season', 's')
            ->where('s.startingDate BETWEEN :fromSeason AND :toSeason')
            ->setParameter('fromSeason', $filter->getFromSeason()->getStartingDate())
            ->setParameter('toSeason', $filter->getToSeason()->getStartingDate());
        }
        return $queryBuilder->getQuery()->getResult();
    }

    // /**
    //  * @return Subscriber[] Returns an array of Subscriber objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Subscriber
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
