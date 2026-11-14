<?php

namespace App\Controller;

use App\Entity\User;
use App\Station\FacebookLiveLookup;
use App\Station\StationBoard;
use App\Station\StationCatalog;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

final class AdminView
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TokenStorageInterface $tokens,
        private readonly StationBoard $board,
        private readonly StationCatalog $catalog,
        private readonly FacebookLiveLookup $facebookLive,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function render(string $template, string $section, array $extra = []): Response
    {
        $user = $this->tokens->getToken()?->getUser();
        \assert($user instanceof User);

        $now = $this->catalog->now();
        $todaySlots = $this->board->slotsForWeekday((int) $now->format('N'));
        $maxHours = 1;
        foreach ($todaySlots as $slot) {
            $hours = $slot->endHour > $slot->startHour
                ? $slot->endHour - $slot->startHour
                : (24 - $slot->startHour) + $slot->endHour;
            $maxHours = max($maxHours, $hours);
        }

        $html = $this->twig->render($template, [
            'section' => $section,
            'adminUsername' => $user->getUsername(),
            'now' => $now,
            'onAir' => $this->board->onAir($now),
            'facebookLive' => $this->facebookLive->current(),
            'facebookPageUrl' => $this->facebookLive->pageUrl(),
            'showCount' => \count($this->catalog->featuredShows()),
            'hostCount' => \count($this->board->hosts()),
            'todaySlots' => $todaySlots,
            'maxHours' => $maxHours,
            'storageReady' => $this->board->storageReady(),
        ] + $extra);

        $response = new Response($html);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
