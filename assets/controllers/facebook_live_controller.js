import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['stage', 'frame', 'badge', 'title', 'link', 'offline'];
    static values = {
        nowPlayingUrl: String,
        embedUrl: String,
    };

    connect() {
        this.apply(this.hasEmbedUrlValue ? this.embedUrlValue : '', null);
        this.timer = setInterval(() => this.refresh(), 30000);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    async refresh() {
        if (!this.nowPlayingUrlValue) {
            return;
        }

        try {
            const response = await fetch(this.nowPlayingUrlValue, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.apply(data.facebookLive?.embedUrl ?? '', data.facebookLive ?? null);
        } catch {
            // Keep the last known embed if the poll fails.
        }
    }

    apply(embedUrl, live) {
        const onAir = Boolean(embedUrl);

        if (this.hasStageTarget) {
            this.stageTarget.hidden = !onAir;
        }
        if (this.hasOfflineTarget) {
            this.offlineTarget.hidden = onAir;
        }

        if (onAir && this.hasFrameTarget && this.frameTarget.getAttribute('src') !== embedUrl) {
            this.frameTarget.src = embedUrl;
        }

        if (live && this.hasBadgeTarget) {
            this.badgeTarget.textContent = live.live ? 'Live on Facebook' : 'Facebook Live';
        }
        if (live && this.hasTitleTarget && live.title) {
            this.titleTarget.textContent = live.title;
        }
        if (live && this.hasLinkTarget && live.permalink) {
            this.linkTarget.href = live.permalink;
        }
    }
}
