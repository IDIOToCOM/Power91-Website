<?php

namespace App\Command;

use App\Station\FacebookLiveLookup;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'app:facebook-live:check',
    description: 'Check whether Facebook Live auto-detect is configured and what the site sees right now.',
)]
final class FacebookLiveCheckCommand extends Command
{
    public function __construct(
        private readonly FacebookLiveLookup $lookup,
        private readonly CacheInterface $cache,
        #[Autowire('%env(default::FACEBOOK_ACCESS_TOKEN)%')]
        private readonly ?string $accessToken = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $token = trim((string) $this->accessToken);

        $io->title('Power 91 Facebook Live');
        $io->text(sprintf(
            'Mode: %s',
            $this->lookup->isDemoLive()
                ? 'local demo (public Facebook video, not a real live)'
                : ($this->lookup->isProfileMode() ? 'personal profile (trial)' : 'Facebook Page'),
        ));
        $io->text(sprintf('Target: %s', $this->lookup->pageId()));
        $io->text(sprintf('Link: %s', $this->lookup->pageUrl()));
        if ('' !== $this->lookup->liveVideoId()) {
            $io->text(sprintf('Producer video: %s', $this->lookup->liveVideoId()));
        }

        if ($this->lookup->isDemoLive()) {
            $io->success('Demo mode is on. The homepage embeds a public Facebook video without a live broadcast.');
        } elseif ('' === $token) {
            $io->warning('FACEBOOK_ACCESS_TOKEN is missing.');
            $io->text([
                '1. Copy .env.local.example to .env.local',
                '2. Paste a Facebook access token into FACEBOOK_ACCESS_TOKEN',
                '3. Run this command again',
            ]);
            $io->note($this->lookup->isProfileMode()
                ? 'Profile trial: User Token (not Page). Permissions: user_videos and publish_video. Do not use the access_token inside /me/accounts.'
                : 'Page mode: use the Page access_token from /me/accounts for the station Page.');

            return Command::FAILURE;
        } else {
            $io->success('FACEBOOK_ACCESS_TOKEN is set (value not shown).');
        }

        $this->cache->delete($this->lookup->cacheKey());
        $live = $this->lookup->current();

        if (null === $live) {
            $io->writeln('<info>No live broadcast right now.</info> The homepage will hide the Facebook player until the studio goes live.');
            if ($this->lookup->isProfileMode()) {
                $io->note([
                    'Personal Go Live is not listed on /me/accounts (that list is Pages only, e.g. “Trial ra”).',
                    'Use a User Token with user_videos, then GET /me/videos?fields=id,live_status while you are live.',
                    'If that JSON has no live_status=LIVE, Facebook is not exposing this personal live to apps — then only a Page live can auto-display.',
                ]);
            }

            return Command::SUCCESS;
        }

        if ($live->detectedLive) {
            $io->writeln('<info>Live on Facebook now.</info>');
        } else {
            $io->warning('Facebook did not report LIVE. Showing the fallback video embed (check token permissions or wait for the next broadcast).');
        }

        $io->definitionList(
            ['Title' => $live->title],
            ['Permalink' => $live->permalink],
            ['Embed' => $live->embedUrl()],
        );

        return Command::SUCCESS;
    }
}
