<?php

namespace App\Repository;

use App\Entity\ProgramSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramSlot>
 */
class ProgramSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgramSlot::class);
    }

    /**
     * @return list<ProgramSlot>
     */
    public function findOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function nextPosition(): int
    {
        $max = $this->createQueryBuilder('slot')
            ->select('MAX(slot.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max + 1;
    }
}
