<?php

namespace App\Station;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FacebookLiveLookup
{
    private const GRAPH_VERSION = 'v21.0';
    private const CACHE_TTL = 30;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(default::FACEBOOK_ACCESS_TOKEN)%')]
        private readonly ?string $accessToken = null,
        #[Autowire('%env(default::FACEBOOK_PAGE_ID)%')]
        private readonly ?string $configuredPageId = null,
        #[Autowire('%env(default::FACEBOOK_VIDEO_URL)%')]
        private readonly ?string $configuredVideoUrl = null,
        #[Autowire('%env(FACEBOOK_LIVE_SOURCE)%')]
        private readonly string $liveSource = 'page',
        #[Autowire('%env(default::FACEBOOK_LIVE_VIDEO_ID)%')]
        private readonly ?string $configuredLiveVideoId = null,
        #[Autowire('%env(default::FACEBOOK_DEMO_LIVE)%')]
        private readonly ?string $demoLive = null,
    ) {
    }

    public function isProfileMode(): bool
    {
        return 'profile' === strtolower(trim($this->liveSource));
    }

    public function pageId(): string
    {
        if ($this->isProfileMode()) {
            return 'me';
        }

        $pageId = trim((string) $this->configuredPageId);

        return '' !== $pageId ? $pageId : StationCatalog::FACEBOOK_PAGE_ID;
    }

    public function pageUrl(): string
    {
        if ($this->isProfileMode()) {
            return 'https://www.facebook.com/';
        }

        return 'https://www.facebook.com/'.$this->pageId();
    }

    public function liveVideoId(): string
    {
        return trim((string) $this->configuredLiveVideoId);
    }

    public function isDemoLive(): bool
    {
        return \in_array(strtolower(trim((string) $this->demoLive)), ['1', 'true', 'yes', 'on'], true);
    }

    public function cacheKey(): string
    {
        if ($this->isDemoLive()) {
            return 'facebook_live_demo';
        }

        $videoId = $this->liveVideoId();
        if ('' !== $videoId) {
            return 'facebook_live_video_'.$videoId;
        }

        return $this->isProfileMode()
            ? 'facebook_live_profile_me'
            : 'facebook_live_'.$this->pageId();
    }

    private function accessToken(): string
    {
        return trim((string) $this->accessToken);
    }

    private function fallbackVideoUrl(): string
    {
        return trim((string) $this->configuredVideoUrl);
    }

    public function current(): ?FacebookLiveBroadcast
    {
        if (!$this->isProfileMode()) {
            $pageId = $this->pageId();
            if ('' === $pageId) {
                return null;
            }
        }

        $graphTarget = $this->isProfileMode() ? 'me' : $this->pageId();

        return $this->cache->get($this->cacheKey(), function (ItemInterface $item) use ($graphTarget): ?FacebookLiveBroadcast {
            $item->expiresAfter(self::CACHE_TTL);

            if ($this->isDemoLive()) {
                $permalink = $this->fallbackVideoUrl();
                if ('' === $permalink) {
                    $permalink = StationCatalog::FACEBOOK_VIDEO_URL;
                }

                return new FacebookLiveBroadcast($permalink, 'Demo Facebook video', true);
            }

            if ('' !== $this->accessToken()) {
                $videoId = $this->liveVideoId();
                if ('' !== $videoId) {
                    $fromId = $this->fromLiveVideoId($videoId);
                    if ($fromId instanceof FacebookLiveBroadcast) {
                        return $fromId;
                    }

                    $permalink = $this->fallbackVideoUrl();
                    if ('' === $permalink) {
                        $permalink = 'https://www.facebook.com/watch/?v='.$videoId;
                    }

                    return new FacebookLiveBroadcast($permalink, 'Facebook Live', false);
                }

                $fromGraph = $this->fromGraph($graphTarget);
                if ($fromGraph instanceof FacebookLiveBroadcast) {
                    return $fromGraph;
                }
                if ($this->isProfileMode()) {
                    $fromVideos = $this->fromUserVideos();
                    if ($fromVideos instanceof FacebookLiveBroadcast) {
                        return $fromVideos;
                    }
                    if (false === $fromVideos) {
                        return null;
                    }
                }
                if (false === $fromGraph) {
                    return null;
                }
            }

            $fallback = $this->fallbackVideoUrl();
            if ('' === $fallback) {
                return null;
            }

            return new FacebookLiveBroadcast(
                $fallback,
                'Power 91 on Facebook Live',
                false,
            );
        });
    }

    /**
     * @return FacebookLiveBroadcast|false|null False means Graph confirmed the video is not live.
     */
    private function fromLiveVideoId(string $videoId): FacebookLiveBroadcast|false|null
    {
        try {
            $response = $this->httpClient->request('GET', sprintf('https://graph.facebook.com/%s/%s', self::GRAPH_VERSION, rawurlencode($videoId)), [
                'query' => [
                    'fields' => 'id,title,permalink_url,status',
                    'access_token' => $this->accessToken(),
                ],
                'timeout' => 4,
            ]);

            /** @var array{error?: array{message?: string, code?: int}, id?: string, title?: string, permalink_url?: string, status?: string} $payload */
            $payload = $response->toArray(false);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Facebook Live video lookup failed.', [
                    'status' => $response->getStatusCode(),
                    'error' => $payload['error']['message'] ?? 'unknown',
                    'code' => $payload['error']['code'] ?? null,
                ]);

                return null;
            }

            $status = strtoupper(trim((string) ($payload['status'] ?? '')));
            if (!\in_array($status, ['LIVE', 'LIVE_NOW'], true)) {
                return false;
            }

            $permalink = $this->absoluteFacebookUrl((string) ($payload['permalink_url'] ?? ''));
            if (null === $permalink) {
                $permalink = 'https://www.facebook.com/watch/?v='.$videoId;
            }

            $title = trim((string) ($payload['title'] ?? ''));

            return new FacebookLiveBroadcast(
                $permalink,
                '' !== $title ? $title : 'Live on Facebook',
                true,
            );
        } catch (ExceptionInterface|\JsonException|\InvalidArgumentException $exception) {
            $this->logger->warning('Facebook Live video lookup errored.', ['exception' => $exception]);

            return null;
        }
    }

    /**
     * Personal timeline lives often do not appear on /live_videos. /me/videos + live_status can.
     *
     * @return FacebookLiveBroadcast|false|null False means Graph returned videos but none are LIVE.
     */
    private function fromUserVideos(): FacebookLiveBroadcast|false|null
    {
        try {
            $response = $this->httpClient->request('GET', sprintf('https://graph.facebook.com/%s/me/videos', self::GRAPH_VERSION), [
                'query' => [
                    'fields' => 'id,title,live_status,permalink_url',
                    'type' => 'uploaded',
                    'limit' => 15,
                    'access_token' => $this->accessToken(),
                ],
                'timeout' => 4,
            ]);

            /** @var array{error?: array{message?: string, code?: int}, data?: list<array{id?: string, title?: string, live_status?: string, permalink_url?: string}>} $payload */
            $payload = $response->toArray(false);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Facebook /me/videos lookup failed.', [
                    'status' => $response->getStatusCode(),
                    'error' => $payload['error']['message'] ?? 'unknown',
                    'code' => $payload['error']['code'] ?? null,
                ]);

                return null;
            }

            foreach ($payload['data'] ?? [] as $video) {
                $status = strtoupper(trim((string) ($video['live_status'] ?? '')));
                if (!\in_array($status, ['LIVE', 'LIVE_NOW'], true)) {
                    continue;
                }

                $permalink = $this->absoluteFacebookUrl((string) ($video['permalink_url'] ?? ''));
                $id = trim((string) ($video['id'] ?? ''));
                if (null === $permalink && '' !== $id) {
                    $permalink = 'https://www.facebook.com/watch/?v='.$id;
                }
                if (null === $permalink) {
                    continue;
                }

                $title = trim((string) ($video['title'] ?? ''));

                return new FacebookLiveBroadcast(
                    $permalink,
                    '' !== $title ? $title : 'Live on Facebook',
                    true,
                );
            }

            return false;
        } catch (ExceptionInterface|\JsonException|\InvalidArgumentException $exception) {
            $this->logger->warning('Facebook /me/videos lookup errored.', ['exception' => $exception]);

            return null;
        }
    }

    /**
     * @return FacebookLiveBroadcast|false|null False means Graph confirmed nothing is live.
     */
    private function fromGraph(string $target): FacebookLiveBroadcast|false|null
    {
        try {
            $response = $this->httpClient->request('GET', sprintf('https://graph.facebook.com/%s/%s/live_videos', self::GRAPH_VERSION, rawurlencode($target)), [
                'query' => [
                    'broadcast_status' => ['LIVE'],
                    'fields' => 'id,title,permalink_url,status',
                    'access_token' => $this->accessToken(),
                ],
                'timeout' => 4,
            ]);

            /** @var array{error?: array{message?: string, type?: string, code?: int}, data?: list<array{title?: string, permalink_url?: string, status?: string}>} $payload */
            $payload = $response->toArray(false);

            if ($response->getStatusCode() >= 400) {
                $this->logger->warning('Facebook Live Graph lookup failed.', [
                    'status' => $response->getStatusCode(),
                    'error' => $payload['error']['message'] ?? 'unknown',
                    'code' => $payload['error']['code'] ?? null,
                ]);

                return null;
            }
            $video = $payload['data'][0] ?? null;
            if (!\is_array($video)) {
                return false;
            }

            $permalink = $this->absoluteFacebookUrl((string) ($video['permalink_url'] ?? ''));
            if (null === $permalink) {
                return false;
            }

            $title = trim((string) ($video['title'] ?? ''));

            return new FacebookLiveBroadcast(
                $permalink,
                '' !== $title ? $title : 'Power 91 on Facebook Live',
                true,
            );
        } catch (ExceptionInterface|\JsonException|\InvalidArgumentException $exception) {
            $this->logger->warning('Facebook Live Graph lookup errored.', ['exception' => $exception]);

            return null;
        }
    }

    private function absoluteFacebookUrl(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            $url = 'https://www.facebook.com'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!\in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com'], true)) {
            return null;
        }

        if (!str_starts_with((string) ($parts['scheme'] ?? ''), 'https')) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return 'https://'.$host.$path.$query;
    }
}
