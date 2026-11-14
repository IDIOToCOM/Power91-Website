<?php

namespace App\Entity;

use App\Repository\ProgramSlotRepository;
use App\Station\ScheduleSlot;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProgramSlotRepository::class)]
#[ORM\Table(name: 'program_slot')]
class ProgramSlot
{
    /**
     * @var array<string, int>
     */
    public const WEEKDAYS = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $title = '';

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $hostName = '';

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $genre = '';

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 23)]
    private int $startHour = 6;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 24)]
    private int $endHour = 10;

    /**
     * @var list<int>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Pick at least one day.')]
    #[Assert\All([new Assert\Choice(choices: [1, 2, 3, 4, 5, 6, 7])])]
    private array $weekdays = [1];

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function getHostName(): string
    {
        return $this->hostName;
    }

    public function setHostName(string $hostName): void
    {
        $this->hostName = trim($hostName);
    }

    public function getGenre(): string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): void
    {
        $this->genre = trim($genre);
    }

    public function getStartHour(): int
    {
        return $this->startHour;
    }

    public function setStartHour(int $startHour): void
    {
        $this->startHour = $startHour;
    }

    public function getEndHour(): int
    {
        return $this->endHour;
    }

    public function setEndHour(int $endHour): void
    {
        $this->endHour = $endHour;
    }

    /**
     * @return list<int>
     */
    public function getWeekdays(): array
    {
        return $this->weekdays;
    }

    /**
     * @param list<int|string> $weekdays
     */
    public function setWeekdays(array $weekdays): void
    {
        $days = array_map(static fn (int|string $day): int => (int) $day, $weekdays);
        sort($days);
        $this->weekdays = array_values(array_unique($days));
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function weekdayLabel(): string
    {
        $names = array_flip(self::WEEKDAYS);
        $labels = [];
        foreach ($this->weekdays as $day) {
            if (isset($names[$day])) {
                $labels[] = $names[$day];
            }
        }

        return implode(', ', $labels);
    }

    public function windowLabel(): string
    {
        return sprintf('%s – %s', ScheduleSlot::formatHour($this->startHour), ScheduleSlot::formatHour($this->endHour));
    }

    public function toScheduleSlot(): ScheduleSlot
    {
        return new ScheduleSlot($this->title, $this->hostName, $this->genre, $this->startHour, $this->endHour, $this->weekdays);
    }

    public static function fromScheduleSlot(ScheduleSlot $slot, int $position): self
    {
        $entity = new self();
        $entity->setTitle($slot->title);
        $entity->setHostName($slot->host);
        $entity->setGenre($slot->genre);
        $entity->setStartHour($slot->startHour);
        $entity->setEndHour($slot->endHour);
        $entity->setWeekdays($slot->weekdays);
        $entity->setPosition($position);

        return $entity;
    }

    #[Assert\Callback]
    public function validateHours(ExecutionContextInterface $context): void
    {
        if ($this->startHour === $this->endHour) {
            $context->buildViolation('The end time has to be different from the start.')->atPath('endHour')->addViolation();
        }
    }
}
