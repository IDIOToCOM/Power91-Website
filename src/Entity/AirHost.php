<?php

namespace App\Entity;

use App\Repository\AirHostRepository;
use App\Station\Host;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AirHostRepository::class)]
#[ORM\Table(name: 'air_host')]
class AirHost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $role = '';

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $showName = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 600)]
    private string $bio = '';

    #[ORM\Column(length: 4)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 4)]
    private string $initials = '';

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Use a color like #1a237e.')]
    private string $accent = '#1a237e';

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = trim($role);
    }

    public function getShowName(): string
    {
        return $this->showName;
    }

    public function setShowName(string $showName): void
    {
        $this->showName = trim($showName);
    }

    public function getBio(): string
    {
        return $this->bio;
    }

    public function setBio(string $bio): void
    {
        $this->bio = trim($bio);
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): void
    {
        $this->initials = strtoupper(trim($initials));
    }

    public function getAccent(): string
    {
        return $this->accent;
    }

    public function setAccent(string $accent): void
    {
        $this->accent = strtolower(trim($accent));
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function toHost(): Host
    {
        return new Host($this->name, $this->role, $this->showName, $this->bio, $this->initials, $this->accent);
    }

    public static function fromHost(Host $host, int $position): self
    {
        $entity = new self();
        $entity->setName($host->name);
        $entity->setRole($host->role);
        $entity->setShowName($host->show);
        $entity->setBio($host->bio);
        $entity->setInitials($host->initials);
        $entity->setAccent($host->accent);
        $entity->setPosition($position);

        return $entity;
    }
}
