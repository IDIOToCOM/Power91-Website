<?php

namespace App\Tests\Station;

use App\Station\FacebookLiveLookup;
use App\Station\StationCatalog;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FacebookLiveLookupTest extends TestCase
{
    public function testPageLiveSlotIsUsedWhenNoAccessToken(): void
    {
        $http = new MockHttpClient(static function (): MockResponse {
            self::fail('Graph should not be called without an access token.');
        });

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), '', StationCatalog::FACEBOOK_PAGE_ID, StationCatalog::FACEBOOK_VIDEO_URL, 'page');
        $live = $lookup->current();

        self::assertNotNull($live);
        self::assertFalse($live->detectedLive);
        self::assertSame(StationCatalog::FACEBOOK_VIDEO_URL, $live->permalink);
        self::assertStringContainsString(rawurlencode(StationCatalog::FACEBOOK_VIDEO_URL), $live->embedUrl());
    }

    public function testGraphLiveVideoBecomesTheHomepageEmbed(): void
    {
        $permalink = 'https://www.facebook.com/100063770387140/videos/1113533604714347/';
        $http = new MockHttpClient([
            new MockResponse(json_encode([
                'data' => [[
                    'id' => '1113533604714347',
                    'title' => 'Tug-anan sa Power 91',
                    'permalink_url' => $permalink,
                    'status' => 'LIVE',
                ]],
            ], \JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), 'page-token', StationCatalog::FACEBOOK_PAGE_ID, StationCatalog::FACEBOOK_VIDEO_URL, 'page');
        $live = $lookup->current();

        self::assertNotNull($live);
        self::assertTrue($live->detectedLive);
        self::assertSame($permalink, $live->permalink);
        self::assertSame('Tug-anan sa Power 91', $live->title);
    }

    public function testGraphEmptyListMeansNotLive(): void
    {
        $http = new MockHttpClient([
            new MockResponse(json_encode(['data' => []], \JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), 'page-token', StationCatalog::FACEBOOK_PAGE_ID, StationCatalog::FACEBOOK_VIDEO_URL, 'page');

        self::assertNull($lookup->current());
    }

    public function testConfiguredPageIdIsUsedForGraphLookup(): void
    {
        $permalink = 'https://www.facebook.com/your-test-page/videos/99/';
        $http = new MockHttpClient(static function (string $method, string $url) use ($permalink): MockResponse {
            self::assertStringContainsString('/555111222/live_videos', $url);

            return new MockResponse(json_encode([
                'data' => [[
                    'id' => '99',
                    'title' => 'Test live',
                    'permalink_url' => $permalink,
                    'status' => 'LIVE',
                ]],
            ], \JSON_THROW_ON_ERROR));
        });

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), 'page-token', '555111222', '', 'page');
        $live = $lookup->current();

        self::assertNotNull($live);
        self::assertTrue($live->detectedLive);
        self::assertSame('555111222', $lookup->pageId());
        self::assertSame($permalink, $live->permalink);
    }

    public function testProfileModeQueriesMeLiveVideos(): void
    {
        $permalink = 'https://www.facebook.com/you/videos/1/';
        $http = new MockHttpClient(static function (string $method, string $url) use ($permalink): MockResponse {
            self::assertStringContainsString('/me/live_videos', $url);

            return new MockResponse(json_encode([
                'data' => [[
                    'id' => '1',
                    'title' => 'My live',
                    'permalink_url' => $permalink,
                    'status' => 'LIVE',
                ]],
            ], \JSON_THROW_ON_ERROR));
        });

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), 'user-token', '', '', 'profile');

        self::assertTrue($lookup->isProfileMode());
        self::assertSame('me', $lookup->pageId());
        $live = $lookup->current();
        self::assertNotNull($live);
        self::assertTrue($live->detectedLive);
        self::assertSame($permalink, $live->permalink);
    }

    public function testProducerVideoIdIsReadWhenLive(): void
    {
        $permalink = 'https://www.facebook.com/watch/?v=1655934622838688';
        $http = new MockHttpClient(static function (string $method, string $url) use ($permalink): MockResponse {
            self::assertStringContainsString('/1655934622838688', $url);
            self::assertStringNotContainsString('live_videos', $url);

            return new MockResponse(json_encode([
                'id' => '1655934622838688',
                'title' => 'Main account live',
                'permalink_url' => $permalink,
                'status' => 'LIVE_NOW',
            ], \JSON_THROW_ON_ERROR));
        });

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), 'user-token', '', '', 'profile', '1655934622838688');
        $live = $lookup->current();

        self::assertNotNull($live);
        self::assertTrue($live->detectedLive);
        self::assertSame($permalink, $live->permalink);
        self::assertSame('Main account live', $live->title);
    }

    public function testProfileModeUsesMeVideosLiveStatus(): void
    {
        $permalink = 'https://www.facebook.com/watch/?v=99';
        $http = new MockHttpClient(static function (string $method, string $url) use ($permalink): MockResponse {
            if (str_contains($url, '/me/live_videos')) {
                return new MockResponse(json_encode(['data' => []], \JSON_THROW_ON_ERROR));
            }

            self::assertStringContainsString('/me/videos', $url);

            return new MockResponse(json_encode([
                'data' => [[
                    'id' => '99',
                    'title' => 'Timeline live',
                    'live_status' => 'LIVE',
                    'permalink_url' => $permalink,
                ]],
            ], \JSON_THROW_ON_ERROR));
        });

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), 'user-token', '', '', 'profile');
        $live = $lookup->current();

        self::assertNotNull($live);
        self::assertTrue($live->detectedLive);
        self::assertSame($permalink, $live->permalink);
    }

    public function testDemoModeShowsPublicVideoWithoutGraph(): void
    {
        $http = new MockHttpClient(static function (): MockResponse {
            self::fail('Graph should not be called in demo mode.');
        });

        $lookup = new FacebookLiveLookup($http, new ArrayAdapter(), new NullLogger(), '', '', StationCatalog::FACEBOOK_VIDEO_URL, 'page', '', '1');
        $live = $lookup->current();

        self::assertTrue($lookup->isDemoLive());
        self::assertNotNull($live);
        self::assertTrue($live->detectedLive);
        self::assertSame(StationCatalog::FACEBOOK_VIDEO_URL, $live->permalink);
    }
}
