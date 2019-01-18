<?php

declare(strict_types=1);

namespace IkastenBot\Repository;

use Doctrine\ORM\EntityRepository;
use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\User;

class GanttProjectRepository extends EntityRepository
{
    /**
     * Get latest GanttProject from user.
     *
     * @param User $user The user for whom the latest GanttProject will be
     *                   fetched
     *
     * @return null|GanttProject Returns null if no GanttProject has been found
     */
    public function findLatestGanttProject(User $user): ?GanttProject
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('gp')
            ->from(GanttProject::class, 'gp')
            ->where('gp.user = :user_id')
            ->orderBy('gp.version', 'DESC')
            ->setParameter('user_id', $user->getId())
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}
