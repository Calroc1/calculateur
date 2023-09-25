<?php

namespace App\Repository\Campaign;

use App\Entity\Campaign\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;

/**
 * @method Campaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campaign[]    findAll()
 * @method Campaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    public function findByIds($ids)
    {
        if (empty($ids))
            return [];

        return $this->createQueryBuilder('c')
            ->where($this->createQueryBuilder('c')->expr()->in('c.id', $ids))
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserCampaigns($user, $params = [], $count = null, $builderOnly = false)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.organism', 'o')
            ->where('(c.organism = :organism OR o.parent = :organism)')
            ->addOrderBy('c.dateEnd', 'DESC')
            ->setParameter('organism', $user->getOrganism());

        foreach ($params as $column => $val) {
            $qb->andWhere('c.' . $column . ' = :val')->setParameter('val', $val);
        }
        if ($count)
            $qb->setMaxResults($count);
        return $builderOnly ? $qb : $qb->getQuery()->getResult();
    }

    public function advancedSearchFromRequest($request, $user)
    {
        $qb = $this->createQueryBuilder('c');
        if ($request->query->has('organism')) {
            // TO DO CHECK USER PROPRIETAIRE ORGANISME
            /*$qb
                ->andWhere('c.organism = :organism')
                ->setParameter('organism', $request->query->get('organism'));*/
        } else {
            $qb
                ->leftJoin('c.organism', 'o')
                ->andWhere('(c.organism = :organism OR o.parent = :organism)')
                ->setParameter('organism', $user->getOrganism());
        }
        if ($request->query->has('dateEnd') || $request->query->has('dateStart')) {
            $dateEnd = \Datetime::createFromFormat('Y-m-d', $request->query->get('dateEnd'));
            $dateStart = \Datetime::createFromFormat('Y-m-d', $request->query->get('dateStart'));
            if ($dateEnd && $dateStart) {
                $qb
                    ->andWhere('(c.dateStart >= :dateStart AND c.dateStart <= :dateEnd) OR (c.dateEnd >= :dateStart AND c.dateEnd <= :dateEnd) OR (c.dateStart <= :dateStart AND c.dateEnd >= :dateEnd) ')
                    ->setParameter('dateStart', $dateStart)->setParameter('dateEnd', $dateEnd);
            } else if ($dateStart) {
                $qb
                    ->andWhere('c.dateStart >= :date OR c.dateEnd >= :date')
                    ->setParameter('date', $dateStart);
            } else if ($dateEnd) {
                $qb
                    ->andWhere('c.dateStart <= :date OR c.dateEnd <= :date')
                    ->setParameter('date', $dateEnd);
            }
        }
        if ($request->query->has('country')) {
            $qb
                ->andWhere('c.country = :country')
                ->setParameter('country', $request->query->get('country'));
        }
        if ($request->query->has('status')) {
            switch ($request->query->get('status')) {
                case 'STARTED':
                    $qb->andWhere('c.completed = false')
                        ->andWhere(':now < c.dateEnd')
                        ->andWhere('DATE_DIFF(c.dateCreation, :now) <= 365');
                    break;
                case 'COMPLETED':
                    $qb
                        ->andWhere('c.completed = true')
                        ->andWhere(':now < c.dateEnd')
                        ->andWhere('DATE_DIFF(c.dateCreation, :now) <= 365');
                    break;
                case 'FINISHED':
                    $qb
                        ->andWhere(':now > c.dateEnd')
                        ->andWhere('DATE_DIFF(c.dateCreation, :now) <= 365');
                    break;
                case 'ARCHIVED':
                    $qb->andWhere('DATE_DIFF(c.dateCreation, :now) > 365');
                    break;
            }
            $qb->setParameter('now', new \Datetime());
        }
        return $qb->getQuery()->getResult();
    }

    public function advancedSearchFromRequestPost($request, $user)
    {
        $country = $request->request->get("country");
        $status = $request->request->get("status");
        $dateStart = $request->request->get("dateStart");
        $dateEnd = $request->request->get("dateEnd");
        $campaignsSelect = $request->request->get("campaigns-select");

        $qb = $this->createQueryBuilder('c');

        $qb
            ->leftJoin('c.organism', 'o')
            ->andWhere('(c.organism = :organism OR o.parent = :organism)')
            ->setParameter('organism', $user->getOrganism());

        if ($dateEnd || $dateStart) {
            $dateEnd = \Datetime::createFromFormat('Y-m-d', $dateEnd);
            $dateStart = \Datetime::createFromFormat('Y-m-d', $dateStart);

            if ($dateEnd && $dateStart) {
                $qb
                    ->andWhere('(c.dateStart >= :dateStart AND c.dateStart <= :dateEnd) OR (c.dateEnd >= :dateStart AND c.dateEnd <= :dateEnd) OR (c.dateStart <= :dateStart AND c.dateEnd >= :dateEnd) ')
                    ->setParameter('dateStart', $dateStart)->setParameter('dateEnd', $dateEnd);
            } else if ($dateStart) {
                $qb
                    ->andWhere('c.dateStart >= :date OR c.dateEnd >= :date')
                    ->setParameter('date', $dateStart);
            } else if ($dateEnd) {
                $qb
                    ->andWhere('c.dateStart <= :date OR c.dateEnd <= :date')
                    ->setParameter('date', $dateEnd);
            }
        }

        if ($campaignsSelect && $campaignsSelect != 'all') {
            $qb
                ->andWhere('c.id IN (:campaigns)')
                ->setParameter('campaigns', $campaignsSelect);
        }

        if ($country && $country != 'all') {
            $qb
                ->andWhere('c.country = :country')
                ->setParameter('country', $country);
        }

        if ($status && $status != 'all') {
            switch ($status) {
                case 'STARTED':
                    $qb->andWhere('c.completed = false')
                        ->andWhere(':now < c.dateEnd')
                        ->andWhere('DATE_DIFF(c.dateCreation, :now) <= 365');
                    break;
                case 'COMPLETED':
                    $qb
                        ->andWhere('c.completed = true')
                        ->andWhere(':now < c.dateEnd')
                        ->andWhere('DATE_DIFF(c.dateCreation, :now) <= 365');
                    break;
                case 'FINISHED':
                    $qb
                        ->andWhere(':now > c.dateEnd')
                        ->andWhere('DATE_DIFF(c.dateCreation, :now) <= 365');
                    break;
                case 'ARCHIVED':
                    $qb->andWhere('DATE_DIFF(c.dateCreation, :now) > 365');
                    break;
            }

            $qb->setParameter('now', new \Datetime());
        }

        return $qb->getQuery()->getResult();
    }
}
