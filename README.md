# Power 91

Dumaguete music variety station site. Clone it, then start it. No extra coding.

## On another PC (manager demo)

1. Install [Git](https://git-scm.com/download/win) if it is missing.
2. Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) and wait until it says it is running.
3. Clone this repository, then double-click **`start.bat`**.
4. Open **http://localhost:8080**

The first launch downloads images and can take several minutes. Leave the window open while you look at the site.

If this PC already has PHP 8.4+ and Composer, `start.bat` uses those instead. The site is still **http://127.0.0.1:8080**.

## Station content

Edit `src/Station/StationCatalog.php` for address, hosts, and the program grid. Save and refresh.

## Facebook Live on the homepage (one-time setup)

When this is configured, every new Facebook Live on the Power 91 page shows on the website automatically. Stopping today and going live again tomorrow needs **no** new permission — the same token keeps working until Meta expires or revokes it.

You need a **Page admin** (or someone who manages the Power 91 Facebook Page) for about 10 minutes. You do **not** put the station’s Facebook password in the website.

### 1. Meta app (manager or Page admin)

1. Open [developers.facebook.com](https://developers.facebook.com/) and sign in with a Facebook account that **manages** [Power 91.7 DYGB FM](https://www.facebook.com/100063770387140).
2. **My Apps** → **Create App** → **Other** or **Business** → name it (e.g. `Power 91 Website`).

### 2. Page access token

1. Open [Graph API Explorer](https://developers.facebook.com/tools/explorer/).
2. Choose your new app at the top.
3. **Generate Access Token** → add permissions: `pages_show_list`, `pages_read_engagement`.
4. Approve access to the **Power 91** page when asked.
5. In the query box, run: `GET` `/me/accounts`
6. In the JSON, find the Power 91 entry and copy its **`access_token`** (that is the **Page** token, not the short user token at the top).
7. In Meta’s docs, exchange for a **long-lived Page access token** if you can, so it does not expire every hour.

### 3. Put the token on this machine

```powershell
cd c:\Users\User\Documents\power-91-website
copy .env.local.example .env.local
```

Edit `.env.local` and set:

```dotenv
FACEBOOK_ACCESS_TOKEN=paste_the_page_token_here
```

Restart the site (`start.bat` or `symfony server:stop` then `symfony serve`).

### 4. Verify

```powershell
php bin/console app:facebook-live:check
```

- **Token missing** — follow step 3.
- **No live broadcast right now** — correct when the page is offline; homepage player stays hidden.
- **Live on Facebook now** — homepage should show that stream within ~30 seconds.

While live, you can also open `http://127.0.0.1:8080/now-playing` and look for `"facebookLive": { "live": true, ... }`.

### Day to day

Hosts start Facebook Live on the **Page** the same way they always do. Nobody pastes embed codes into the site.

### Try it with your own Facebook (not a personal profile)

Facebook’s API only reports lives from a **Page**, not from your personal timeline. Use your Facebook login to **create a Page**, then go live **as that Page**.

1. On Facebook: **Pages** → **Create new Page** (any name is fine for a test).
2. In [Graph API Explorer](https://developers.facebook.com/tools/explorer/), generate a token with `pages_show_list` and `pages_read_engagement`, then `GET` `/me/accounts`.
3. Copy **your** Page’s `id` and `access_token` (the Page you created, not Power 91).
4. Put them in `.env.local`:

```dotenv
FACEBOOK_PAGE_ID=your_page_id
FACEBOOK_ACCESS_TOKEN=your_page_token
FACEBOOK_VIDEO_URL=
```

5. Restart the site, then:

```powershell
php bin/console app:facebook-live:check
```

6. On Facebook, switch to that Page and **Go live**. Within about 30 seconds the homepage player should show your live.

When you are ready for the real station, set `FACEBOOK_PAGE_ID` back to `100063770387140` and use a token for the Power 91 Page.

## Studio board

There is no public sign-up and no admin link in the navigation. Open **http://127.0.0.1:8080/admin-panel** directly. The pre-made account is `studio@power91.local` / `ChangeMeAdmin91`. Guessing `/admin-panel/dashboard` without that session returns a 404.
