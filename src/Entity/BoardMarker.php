<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'board_marker')]
class BoardMarker
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $markerKey,
    ) {
    }

    public function getMarkerKey(): string
    {
        return $this->markerKey;
    }
}
