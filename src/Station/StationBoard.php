<?php

namespace App\Station;

use App\Entity\AirHost;
use App\Entity\BoardMarker;
use App\Entity\ProgramSlot;
use App\Repository\AirHostRepository;
use App\Repository\ProgramSlotRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StationBoard
{
    private ?bool $ready = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StationCatalog $catalog,
        private readonly ProgramSlotRepository $slots,
        private readonly AirHostRepository $hosts,
    ) {
    }

    public function storageReady(): bool
    {
        if (null !== $this->ready) {
            return $this->ready;
        }

        try {
            $this->ready = $this->entityManager->getConnection()->createSchemaManager()->tablesExist([
                'program_slot',
                'air_host',
                'board_marker',
            ]);
        } catch (\Throwable) {
            $this->ready = false;
        }

        return $this->ready;
    }

    /**
     * @return list<ScheduleSlot>
     */
    public function schedule(): array
    {
        if (!$this->storageReady()) {
            return $this->catalog->schedule();
        }

        $this->seedSchedule();

        return array_map(
            static fn (ProgramSlot $slot): ScheduleSlot => $slot->toScheduleSlot(),
            $this->slots->findOrdered(),
        );
    }

    /**
     * @return list<ProgramSlot>
     */
    public function slotRecords(): array
    {
        if (!$this->storageReady()) {
            return [];
        }

        $this->seedSchedule();

        return $this->slots->findOrdered();
    }

    /**
     * @return list<Host>
     */
    public function hosts(): array
    {
        if (!$this->storageReady()) {
            return $this->catalog->hosts();
        }

        $this->seedHosts();

        return array_map(
            static fn (AirHost $host): Host => $host->toHost(),
            $this->hosts->findOrdered(),
        );
    }

    /**
     * @return list<AirHost>
     */
    public function hostRecords(): array
    {
        if (!$this->storageReady()) {
            return [];
        }

        $this->seedHosts();

        return $this->hosts->findOrdered();
    }

    public function onAir(?\DateTimeImmutable $at = null): ScheduleSlot
    {
        return $this->catalog->onAir($at, $this->schedule());
    }

    /**
     * @return list<ScheduleSlot>
     */
    public function slotsForWeekday(int $weekday): array
    {
        return $this->catalog->slotsForWeekday($weekday, $this->schedule());
    }

    /**
     * @return list<array{name: string, weekday: int, slots: list<ScheduleSlot>}>
     */
    public function weeklyGrid(): array
    {
        return $this->catalog->weeklyGrid($this->schedule());
    }

    private function seedSchedule(): void
    {
        if (null !== $this->entityManager->find(BoardMarker::class, 'schedule')) {
            return;
        }

        $position = 0;
        foreach ($this->catalog->schedule() as $slot) {
            $this->entityManager->persist(ProgramSlot::fromScheduleSlot($slot, $position));
            ++$position;
        }

        $this->entityManager->persist(new BoardMarker('schedule'));
        $this->entityManager->flush();
    }

    private function seedHosts(): void
    {
        if (null !== $this->entityManager->find(BoardMarker::class, 'hosts')) {
            return;
        }

        $position = 0;
        foreach ($this->catalog->hosts() as $host) {
            $this->entityManager->persist(AirHost::fromHost($host, $position));
            ++$position;
        }

        $this->entityManager->persist(new BoardMarker('hosts'));
        $this->entityManager->flush();
    }
}
