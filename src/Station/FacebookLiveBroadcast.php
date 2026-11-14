<?php

namespace App\Station;

final readonly class FacebookLiveBroadcast
{
    public function __construct(
        public string $permalink,
        public string $title,
        public bool $detectedLive,
    ) {
    }

    public function embedUrl(): string
    {
        return 'https://www.facebook.com/plugins/video.php?'.http_build_query([
            'href' => $this->permalink,
            'show_text' => 'false',
            'width' => 734,
            'autoplay' => 'true',
        ], '', '&', \PHP_QUERY_RFC3986);
    }

    /**
     * @return array{live: bool, title: string, embedUrl: string, permalink: string}
     */
    public function toArray(): array
    {
        return [
            'live' => $this->detectedLive,
            'title' => $this->title,
            'embedUrl' => $this->embedUrl(),
            'permalink' => $this->permalink,
        ];
    }
}
