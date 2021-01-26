<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @SuppressWarnings(PHPMD)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }
    public function grandTotalPerSeason(): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, season.name')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->groupBy('season.name')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }

    public function totalPerSeason(): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, season.name, subscriber.gender')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->leftJoin('App\Entity\Subscriber', 'subscriber', 'WITH', 'subscription.subscriber = subscriber.id')
            ->groupBy('season.name, subscriber.gender')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }

    public function getQueryForTotalPerSeason(): string
    {
        return "SELECT case when subscriber.gender= 'H' then count(subscription.subscriber) else 0 end AS totalMale,
        case when subscriber.gender= 'F' then count(subscription.subscriber) else 0 end AS totalFemale,
        season.name AS seasonName
        FROM App\Entity\Subscription subscription
        JOIN App\Entity\Season season
        WITH season.id=subscription.season
        JOIN App\Entity\Subscriber subscriber
        WITH subscriber.id=subscription.subscriber
        GROUP BY seasonName, subscriber.gender";
    }

    public function totalLicencesPerSeason(): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, licence.name, season.name AS seasonName')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->leftJoin('App\Entity\Licence', 'licence', 'WITH', 'licence.id=subscription.licence')
            ->groupBy('season.name, licence.name')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }

    public function totalLicencesPerSeasonPerCategory(string $categoryFilter): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, licence.name, season.name AS seasonName')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->leftJoin('App\Entity\Licence', 'licence', 'WITH', 'licence.id=subscription.licence')
            ->leftJoin('App\Entity\Category', 'category', 'WITH', 'subscription.category = category.id')
            ->where('category.newGroup = :categoryFilter')
            ->setParameter('categoryFilter', $categoryFilter)
            ->groupBy('season.name, licence.name')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|null $categoryLabel
     * @param string|null $licenceAcronym
     * @return Subscription
     */
    public function findSubscriptionsBySeason(
        ?string $categoryLabel,
        ?string $licenceAcronym
    ) {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, season.name, subscriber.gender')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->leftJoin('App\Entity\Category', 'category', 'WITH', 'subscription.category = category.id')
            ->leftJoin('App\Entity\Licence', 'licence', 'WITH', 'subscription.licence = licence.id')
            ->leftJoin('App\Entity\Subscriber', 'subscriber', 'WITH', 'subscription.subscriber = subscriber.id')
            ->where('licence.acronym = :licenceAcronym')
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->andWhere('category.label = :categoryLabel')
            ->setParameter('categoryLabel', $categoryLabel)
            ->groupBy('season.name, subscriber.gender')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|null $status
     * @param string|null $seasonName
     * @param string|null $licenceAcronym
     * @param string|null $secondStatus
     * @return Subscription
     * @throws NonUniqueResultException
     */
    public function findAllSubscribersForSeasonByLicenceByStatus(
        ?string $status,
        ?string $secondStatus,
        ?string $seasonName,
        ?string $licenceAcronym
    ) {
        return $this->createQueryBuilder('sub')
            ->select('COUNT(sub.subscriber)')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->innerJoin('App\Entity\Status', 'st', 'WITH', 'st.id = sub.status')
            ->join('App\Entity\Licence', 'l', 'WITH', 'l.id=sub.licence')
            ->andWhere('s.name = :seasonName')
            ->andWhere('st.label = :status')
            ->orWhere('st.label = :secondStatus')
            ->andWhere('l.acronym = :licenceAcronym')
            ->setParameter('seasonName', $seasonName)
            ->setParameter('status', $status)
            ->setParameter('secondStatus', $secondStatus)
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function subscribersByYearByLicences(?string $season): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('licence.acronym as label, COUNT(subscription.subscriber) as subscribersCount')
            ->leftJoin('App\Entity\Licence', 'licence', 'WITH', 'licence.id=subscription.licence')
            ->innerJoin('App\Entity\Season', 'season', 'WITH', 'season.id=subscription.season')
            ->where('season.name = :season')
            ->setParameter('season', $season)
            ->groupBy('licence.acronym')
            ->getQuery()
            ->getResult();
    }

    public function subscribersByYearByCategories(?string $season): array
    {
        return $this->createQueryBuilder('sub')
            ->select('c.label as label, COUNT(sub.category) as subscribersCount')
            ->leftJoin('App\Entity\Category', 'c', 'WITH', 'c.id = sub.category')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->where('s.name = :season')
            ->setParameter('season', $season)
            ->groupBy('sub.category')
            ->orderBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function subscribersByYearByStatus(?string $season): array
    {
        return $this->createQueryBuilder('sub')
            ->select('st.label as label, COUNT(sub.status) as subscribersCount')
            ->leftJoin('App\Entity\Status', 'st', 'WITH', 'st.id = sub.status')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->where('s.name = :season')
            ->setParameter('season', $season)
            ->groupBy('sub.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|null $licenceAcronym
     * @param string|null $seasonName
     * @return Subscription
     * @throws NonUniqueResultException
     */
    public function findAllSubscribersForActualSeason(?string $licenceAcronym, ?string $seasonName)
    {
        return $this->createQueryBuilder('sub')
            ->select('COUNT(sub.subscriber)')
            ->innerJoin('App\Entity\Licence', 'l', 'WITH', 'l.id = sub.licence')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->where('l.acronym = :licenceAcronym')
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->andWhere('s.name = :seasonName')
            ->setParameter('seasonName', $seasonName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|null $licenceAcronym
     * @param string|null $seasonName
     * @param string|null $categoryName
     * @return int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function findAllYoungSubscribersForActualSeason(
        ?string $licenceAcronym,
        ?string $seasonName,
        ?string $categoryName
    ) {
        return $this->createQueryBuilder('sub')
            ->select('COUNT(sub.subscriber)')
            ->innerJoin('App\Entity\Licence', 'l', 'WITH', 'l.id = sub.licence')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->innerJoin('App\Entity\Category', 'c', 'WITH', 'c.id = sub.category')
            ->where('l.acronym = :licenceAcronym')
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->andWhere('s.name = :seasonName')
            ->setParameter('seasonName', $seasonName)
            ->andWhere('c.label LIKE :categoryName')
            ->setParameter('categoryName', $categoryName . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|null $seasonName
     * @return array
     */
    public function getLastSubscriber(?string $seasonName)
    {
        return $this->createQueryBuilder('sub')
            ->select('sr.licenceNumber')
            ->innerJoin('App\Entity\Season', 'sn', 'WITH', 'sn.id = sub.season')
            ->innerJoin('App\Entity\Subscriber', 'sr', 'WITH', 'sr.id = sub.subscriber')
            ->where('sn.name = :seasonName')
            ->setParameter('seasonName', $seasonName)
            ->orderBy('sr.licenceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlySubscriptionsByYear(?string $season): array
    {
        return $this->createQueryBuilder('sub')
            ->select('month(sub.subscriptionDate) as month, count(sub.subscriber) as count')
            ->innerJoin('App\Entity\Season', 'sn', 'WITH', 'sn.id = sub.season')
            ->where('sn.name = :seasonName')
            ->setParameter('seasonName', $season)
            ->groupBy('month')
            ->getQuery()
            ->getResult();
    }

// /**
    //  * @return Subscription[] Returns an array of Subscription objects
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
    public function findOneBySomeField($value): ?Subscription
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
