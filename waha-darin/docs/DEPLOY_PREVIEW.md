# Preview deploy (friends testing): one URL + database

This app is **not** deployable as “everything on Vercel”: Vercel does not run PHP (Laravel) or your database. Use a **single container** (this `Dockerfile`) on **Railway**, **Render**, or similar, plus **PostgreSQL** (e.g. **Neon**, recommended for new deploys) or **MySQL** (legacy / Railway-style).

**You only care about Railway right now?** Skip [Build locally](#build-locally) and use the **[Railway deployment runbook](#railway-deploy-runbook)** first. It assumes your **tar of covers/authors is already on the volume** and tells you exactly what to connect so the deployed site behaves like a working local stack (FE + BE + DB + images).

## What the image does

- **Build:** Node builds the Nuxt SPA (`npm run generate`) into `public/_nuxt`.
- **Run:** PHP 7.4 + Apache serves Laravel with `DocumentRoot` = `public/` (same as local: `/api` + SPA).
- **Port:** Listens on `$PORT` (Railway/Render set this automatically).
- **Proxies:** [`app/Http/Middleware/TrustProxies.php`](../app/Http/Middleware/TrustProxies.php) trusts `*` so `APP_URL`/`https` work behind the platform reverse proxy.

<a id="railway-deploy-runbook"></a>

## Railway deployment runbook — FE, BE, DB, and covers (images already uploaded)

Use this when **`storage/app/public`** on the server **already has** your files (books, authors, Voyager folders, `default-book.png`, etc.) and you want **one live URL** where the Nuxt app, Laravel API, MySQL, and image URLs all work together — same idea as local “one working site”, without redoing laptop setup.

### What must be true on Railway

| Layer | Requirement |
|--------|-------------|
| **Volume** | Web service has a volume at **`/var/www/html/storage`** so uploaded files survive redeploys. |
| **Files** | Covers/authors live under **`/var/www/html/storage/app/public/...`** (same layout as local `waha-darin/storage/app/public`). |
| **Symlink** | After each container start, [`docker/entrypoint-preview.sh`](../docker/entrypoint-preview.sh) runs **`php artisan storage:link`** so the browser can request **`/storage/...`** and Apache serves those files. |
| **DB** | MySQL has **rows** for books/authors (and **`books.image`** / author avatar paths that match files on disk). Empty tables after `migrate` alone → no books in the UI even if images exist. Import a **dump** from the environment that produced those paths, or seed/import your data. |
| **Env** | **`APP_URL`** and **`CLIENT_URL`** = your real public **`https://…`** Railway domain (same string). API JSON builds `cover_photo` URLs from [`PublicStorageUrl`](../app/Support/PublicStorageUrl.php) + `APP_URL`. Wrong URL → broken images or wrong links. |
| **DB link** | Web service **`DATABASE_URL`** references the project’s **MySQL** service (private hostname; only works inside Railway). |

### Steps (Railway dashboard + web service shell only)

1. **Web service → Variables** — Set at minimum:
   - **`DATABASE_URL`** — reference MySQL’s `DATABASE_URL` (or equivalent).
   - **`APP_URL`** = `https://YOUR-SERVICE.up.railway.app` (your generated domain; **no** trailing slash is safest).
   - **`CLIENT_URL`** = same value as **`APP_URL`**.
   - **`APP_KEY`** — any secure Laravel key (e.g. run `php artisan key:generate --show` once wherever you have PHP, paste into Railway).
   - **`JWT_SECRET`** — long random string (required for API JWT).
   - **`APP_ENV`** = `production`, **`APP_DEBUG`** = `false` for a public preview.
   - **Email / registration** — Without SMTP, verification emails cannot be delivered. For a **preview** you can set **`SKIP_EMAIL_VERIFICATION=true`** (see [`config/waha.php`](../config/waha.php)): new accounts are marked verified and can log in immediately. For real mail (password reset, verification, borrow notifications), configure **`MAIL_*`** (e.g. Resend, SendGrid, Mailgun) or set **`MAIL_MAILER=log`** only to avoid SMTP errors while testing (emails go to logs, not the inbox).
2. **Do not** set a Docker **build argument** **`API_URL`** to `http://localhost…` on Railway. For this image, the Nuxt build defaults to **`/api`** (same host as the SPA). Wrong `API_URL` → browser calls your laptop instead of Railway.
3. **Deploy current code** — Trigger a deploy from your connected Git branch so Railway runs the latest **`Dockerfile`** build (includes Nuxt under `public/_nuxt` and Laravel with [`PublicStorageUrl`](../app/Support/PublicStorageUrl.php) for `cover_photo` links). After you change **Variables**, redeploy or restart if needed so **`APP_URL`** is applied.
4. **Web service → Shell** (must be **inside** Railway, not only on your Mac):
   ```bash
   php artisan migrate --force
   php artisan passport:install --force
   ```
5. **Load data** — Import the **MySQL dump** that matches your book/author rows and image paths (same dump family as the machine where those cover paths were created). Step-by-step import commands are in **§ F** → *Copy the database from local → Railway* below. This is what makes the deploy **feel like local**: same data + same files.
6. **Permissions** (only if uploads or images return **403**): from [native SSH](https://docs.railway.com/cli/ssh) or shell as root, `chown -R www-data:www-data /var/www/html/storage`.
7. **Verify** (replace your domain):
   - Browser: `https://YOUR-SERVICE.up.railway.app/` → SPA loads.
   - Browser or curl: `https://YOUR-SERVICE.up.railway.app/api/books` → JSON; each item’s **`cover_photo`** should point at **`https://YOUR-SERVICE.../storage/...`** (or a path your browser can load on that host).
   - Open one **`cover_photo`** URL → image should display (proves **volume + symlink + APP_URL + DB paths** align).

### If something fails

- **Empty API / empty home** — Database has no rows; run import or seed after migrate.
- **Books in API but images 404** — File missing under `storage/app/public`, or **`books.image`** path does not match filename on disk, or **`APP_URL`** does not match the URL you use in the browser.
- **Login / 401 on API** — Set **`JWT_SECRET`** on the web service (required for JWT login/register). Run **`php artisan migrate --force`** so auth tables exist.
- **Register returns 500 or never finishes** — Often **SMTP / mail** misconfiguration when the app tries to send the verification email. Use **`SKIP_EMAIL_VERIFICATION=true`** for preview, or fix **`MAIL_*`**, or **`MAIL_MAILER=log`** to swallow sends into logs.
- **Can register / log in but “cannot borrow”** — By design, creating a borrow order requires an **active subscription** with enough quota ([`HasValidSubscription`](../app/Http/Middleware/HasValidSubscription.php)). New users start without an active plan; an admin must create/activate a subscription in **Voyager** (or you seed `subscriptions` + `plans` like local).
- **No emails (orders, password reset)** — Configure real **`MAIL_*`** variables; **`QUEUE_CONNECTION`** is **`sync`** by default so mail sends in-request (no worker needed).
- **Images disappeared after redeploy** — No volume on **`/var/www/html/storage`**, or files were never on the volume.

## How DB, API, SPA, and images connect (one Railway URL)

Everything is served from **one public origin** (e.g. `https://your-app.up.railway.app`):

| Piece | Role | What you configure |
|--------|------|---------------------|
| **Nuxt SPA** | Static assets under `public/_nuxt`; browser runs the app | Baked at **Docker build** time. Default **`API_URL` is `/api`** (same origin). **Do not** set `API_URL` to a full `http://localhost…` URL on Railway or the browser will call your laptop. |
| **Laravel `/api/*`** | JSON API, auth, Passport | **`DATABASE_URL`** (or discrete `DB_*`) so MySQL is reachable inside Railway’s network. **`JWT_SECRET`**, **`APP_KEY`**, **`APP_URL`**. |
| **Same-origin API** | `axios` uses [`client/plugins/axios.js`](../client/plugins/axios.js) + [`client/nuxt.config.js`](../client/nuxt.config.js) | With default `/api`, requests go to `https://your-app/api/...` — no separate “API domain”. |
| **Cover / upload URLs** | Files on disk: `storage/app/public` → symlink `public/storage` (entrypoint runs `storage:link`) | **`APP_URL`** must be your real **HTTPS** Railway URL so [`config/filesystems.php`](../config/filesystems.php) and [`PublicStorageUrl`](../app/Support/PublicStorageUrl.php) build `https://your-app/storage/...`. The **DB** `books.image` (and similar) should be **relative** paths like `books/…/file.jpg`; the API JSON field `cover_photo` is computed in [`Book`](../app/Models/Book.php) (and authors/categories/events) so the **SPA** always gets URLs on **your** domain. If a dump still has full `https://old-host/storage/...` strings, they are rewritten to the current `APP_URL` path. |
| **MySQL** | Book rows, users, Voyager metadata | Link **`DATABASE_URL`** from the MySQL service to the **web** service. Import a dump if you want real data, not only empty tables from `migrate`. |

```mermaid
flowchart LR
  subgraph browser [Browser]
    SPA[Nuxt SPA]
  end
  subgraph railway [Railway web container]
    Apache[Apache + PHP]
    API[Laravel /api]
    Disk["storage/app/public\n+ storage:link"]
    SPA -->|"GET / , /_nuxt"| Apache
    SPA -->|"/api/*"| API
    API --> MySQL[(MySQL)]
    API --> Disk
    SPA -->|"/storage/*"| Apache
    Apache --> Disk
  end
```

## Railway: end-to-end checklist (link DB + images + FE/BE)

From **zero** (new project). If **images are already on the volume**, prefer the **[deployment runbook](#railway-deploy-runbook)** above, then only complete whatever you still lack (usually **variables**, **migrate + passport**, **DB import**).

Do these **in order** the first time (or after a new empty database / volume):

1. **Project & root** — Deploy from GitHub; set **Root Directory** to `waha-darin` if the repo root is the parent folder. Build = **Dockerfile** ([`railway.toml`](../railway.toml)).
2. **MySQL** — Add **MySQL** in the same project.
3. **Link DB → web** — On the **web** service → **Variables** → reference **`DATABASE_URL`** from the MySQL service (Laravel reads it in [`config/database.php`](../config/database.php)).
4. **Public URL variables** — Generate a **public domain** for the web service. Set **`APP_URL`** and **`CLIENT_URL`** to that exact **`https://…`** URL (same value for this single-origin setup).
5. **Secrets** — Set **`APP_KEY`** (e.g. `php artisan key:generate --show` locally), **`JWT_SECRET`** (long random string; see [`config/jwt.php`](../config/jwt.php)), plus any **`MAIL_*`**, Stripe, OAuth keys you need.
6. **Volume** — Attach a volume at **`/var/www/html/storage`** on the web service and **redeploy** (see section **F** below). Without this, uploads and Passport keys do not survive redeploys.
7. **Deploy** — Wait for a green deployment.
8. **Migrations + Passport (in Railway shell on web)** — `php artisan migrate --force` then `php artisan passport:install --force` (see **D** below).
9. **Data** — Optional: import your **MySQL dump** so books/categories match local (see **F §2**). Migrations alone leave tables empty.
10. **Images** — Copy **`storage/app/public`** to the container (covers must exist where the DB paths point). From your Mac: [`scripts/push-storage-public-to-railway.sh`](../scripts/push-storage-public-to-railway.sh) or the **`tar | ssh`** flow in **F §3**. **Skip** if you already uploaded the tar.
11. **Smoke test** — Open the app URL: SPA loads, **`/api`** responds, a book cover loads at **`/storage/...`** if the DB references that path.

**Railway UI note:** There is **no** dashboard button to upload a tarball straight onto a volume. Use **SSH** ([native SSH](https://docs.railway.com/cli/ssh) / the script above) or a temporary **file browser** template if you prefer a browser UI.

## Build locally

Optional: only needed if you want to **test the Docker image on your machine**. **Not required** to complete a Railway deploy.

From the **`waha-darin`** directory (repository root of the Laravel app):

```bash
docker build -t dar-in-preview .
```

Optional: pin API base at build time (default is same-origin `/api`):

```bash
docker build --build-arg API_URL=/api -t dar-in-preview .
```

Run (example):

```bash
docker run --rm -p 8080:8080 -e PORT=8080 \
  -e APP_KEY=base64:... \
  -e APP_URL=http://127.0.0.1:8080 \
  -e CLIENT_URL=http://127.0.0.1:8080 \
  -e DB_HOST=... -e DB_DATABASE=... -e DB_USERNAME=... -e DB_PASSWORD=... \
  dar-in-preview
```

## Railway (step by step)

I cannot log into your Railway account from here; you finish deploy in the browser (or with the CLI after `railway login`). The repo includes [`railway.toml`](../railway.toml) so Railway uses this folder’s `Dockerfile`.

### A. New project from GitHub

1. [railway.app](https://railway.app) → **Login** → **New Project** → **Deploy from GitHub repo**.
2. Pick the repo that contains `waha-darin`.
3. Open the **web service** → **Settings**:
   - If the Git **root** is the parent folder (e.g. `Dar-books`), set **Root Directory** to `waha-darin`.
   - **Build** should use **Dockerfile** (see `railway.toml`). If Railway offers Nixpacks, switch to Dockerfile deploy.

### B. MySQL database

1. In the same project: **New** → **Database** → **MySQL**.
2. On your **web** service → **Variables** → **Add variable reference** (or **Raw editor**):
   - Add **`DATABASE_URL`** and reference the MySQL service’s **`DATABASE_URL`** (or `MYSQL_URL`, depending on what Railway shows).  
   Laravel’s MySQL config in `config/database.php` already supports `DATABASE_URL`.

### C. Required app variables (web service)

In **Variables**, add (use your real public URL from **Settings → Networking → Generate Domain**):

| Variable | Example / notes |
|----------|------------------|
| `APP_KEY` | `php artisan key:generate --show` (any machine with PHP) — paste into Railway |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` for a public preview |
| `APP_URL` | `https://your-service.up.railway.app` |
| `CLIENT_URL` | Same as `APP_URL` |
| `JWT_SECRET` | **Required** for `/api` JWT auth — copy from local `.env` or generate a long random string |
| `SKIP_EMAIL_VERIFICATION` | Set to **`true`** on preview if you have **no SMTP**; new users can register and log in without mail ([`config/waha.php`](../config/waha.php)). Leave unset/false when real **`MAIL_*`** is configured. |
| `MAIL_*`, `MAIL_MAILER` | Required for verification, password reset, and order mail in production-like mode; use **`log`** driver only for debugging (no inbox). |

Optional: copy `STRIPE_*`, `SUPER_ADMIN_EMAIL`, etc. from local `.env` if you need those features on the preview.

For **single-origin** deploys, **do not** set a Docker **build argument** `API_URL` to an external host: the Nuxt build should keep the default **`/api`** so the browser talks to the same Railway hostname. Override only for split-domain setups (e.g. Vercel + separate API).

### D. First deploy: migrate + Passport

**New MySQL on Railway has no tables** until you run Laravel migrations. Deploying the Docker image only starts PHP/Apache; it does **not** create `users`, `books`, etc. If you open the DB and see an empty database (or only system tables), that is normal — run the commands below.

Use the **Railway dashboard shell** on the **web** service (not `railway run` on your laptop): your app uses `DATABASE_URL` with a **private** MySQL hostname that only resolves inside Railway’s network.

After the first successful deploy:

1. **Web service** → **Deployments** → open the latest deployment → **Shell**.

   ```bash
   php artisan migrate --force
   php artisan migrate:status    # optional: should list many “Ran” migrations
   php artisan passport:install --force
   ```

2. `passport:install` must run **after** migrations so `/api/login` works.

3. **Books and categories stay empty** until you load data: import a **SQL dump** from your local MySQL, or after creating an admin user run seeders / Voyager import (e.g. `php artisan db:seed --class=VoyagerDatabaseSeeder` if you use that locally).

### E. CLI (optional)

```bash
npm i -g @railway/cli
cd /path/to/waha-darin
railway login
railway link    # select project
railway up      # deploy current directory
```

### Why this isn’t “deployed for you”

Deploying requires **your** Railway/GitHub authorization. The codebase side is ready: `Dockerfile`, `railway.toml`, entrypoint, and docs above are what you need to click through once.

### F. Book covers, uploads, and cloning your local DB

**Migrations only create empty tables** (structure). They do **not** copy rows from your laptop. To match local, you need **(1) a MySQL dump** and **(2) a real strategy for uploaded files** (covers, avatars, Voyager media).

#### Recommended ways to host images on Railway (documented patterns)

Uploading via random “file share” sites is **not** a good long-term approach. Use one of these instead:

| Approach | When to use | Docs |
|----------|-------------|------|
| **Railway [Volumes](https://docs.railway.com/guides/volumes)** | Keep the same model as local Laravel: files on disk under `storage/`. One replica, preview/staging. Mount a volume so uploads **survive redeploys**. | [Using volumes](https://docs.railway.com/guides/volumes) |
| **S3-compatible object storage** (AWS S3, **Cloudflare R2**, DigitalOcean Spaces, etc.) | Production-style: scalable, CDN-friendly, works with multiple instances. Laravel’s **`s3`** disk is [documented here](https://laravel.com/docs/7.x/filesystem). This repo’s [`config/filesystems.php`](../config/filesystems.php) already defines an `s3` disk; you set `FILESYSTEM_DRIVER=s3` (or point the `public` disk at S3) and env vars `AWS_*` / R2-compatible endpoint. | [Laravel filesystem](https://laravel.com/docs/7.x/filesystem) |

**Volume mount path for this Docker image:** the app lives at **`/var/www/html`**, so typical mounts are:

- **`/var/www/html/storage`** — all of Laravel storage (uploads, `framework`, logs, Passport keys, etc.). **Recommended** for this preview stack.
- **`/var/www/html/storage/app/public`** — only public uploads; the rest of `storage/` stays on the image layer (slightly trickier if you want Passport keys on the volume too).

#### Railway volume: CLI setup (this Dockerfile)

1. From **`waha-darin`** (linked to your web service):

   ```bash
   railway volume add -m /var/www/html/storage
   ```

   Or in the [Railway dashboard](https://docs.railway.com/guides/volumes): add a volume to the web service with mount path **`/var/www/html/storage`**.

2. **Redeploy** the web service so the container starts with the volume attached.

3. **Empty volume = empty `storage/`:** the image’s built-in `storage` tree is hidden by the mount. The preview [entrypoint](../docker/entrypoint-preview.sh) recreates `storage/framework/*`, `storage/logs`, and `storage/app/public`, sets ownership for **`www-data`**, runs **`passport:keys`** / **`storage:link`**, then fixes ownership again so PHP can write.

4. **One-time:** copy your local **`storage/app/public`** (covers, avatars, Voyager folders) into the same path on the server — e.g. interactive **`railway ssh`**, **`scp`** (if you use **`railway ssh --native`** with an SSH key), or a **`tar`** downloaded from **your** private bucket/server. After that, new uploads persist on the volume across deploys.

5. If Apache/PHP still cannot write the volume, set on the **web** service (see [Railway volumes — permissions](https://docs.railway.com/guides/volumes)): **`RAILWAY_RUN_UID=0`**.

**Volume size:** the default is often **500MB**; resize in the volume settings if `storage/app/public` grows (e.g. many full-resolution covers).

**Object storage:** new uploads go to the bucket; existing DB rows that store **paths** like `books/foo.jpg` need either **migrating files into the bucket** with the same keys or **updating URLs** in the database. That’s a deliberate migration, not a one-click deploy.

#### 1) Do you need to deploy cover images separately (local-disk setup)?

**Yes, if you stay on the `public` disk** (files under `storage/app/public/...`, URLs like `https://your-app/storage/...`).

- The **Docker image does not include** your local `storage/app/public` tree (see [`.dockerignore`](../.dockerignore)).
- Each deploy gets a **new container filesystem** unless you use a **volume** (above) or **object storage** (above).
- The preview entrypoint runs **`php artisan storage:link`** so `public/storage` → `storage/app/public` works; you still need the **files** on that path (volume + copy, or S3 + config change).

If the DB stores **full URLs** to another host, you do not need those files on Railway (unusual for this app).

#### 2) Copy the database from local → Railway

On your **Mac** (replace `USER`, `DBNAME`, and paths with yours — often `DBNAME` matches `DB_DATABASE` in `.env`):

```bash
cd /path/to/waha-darin
mysqldump -u USER -p --single-transaction --routines --triggers --set-gtid-purged=OFF DBNAME > /tmp/darin-export.sql
```

Get Railway’s **public** MySQL URL from the **MySQL** service → **Variables** (e.g. `MYSQL_PUBLIC_URL`, looks like `mysql://root:PASSWORD@HOST:PORT/railway`). Import:

```bash
mysql --ssl-mode=REQUIRED -h HOST -P PORT -u root -pPASSWORD railway < /tmp/darin-export.sql
```

(Use the real host, port, password, and database name from Railway; `mysql` client must be installed, e.g. `brew install mysql-client`.)

**Caveats:** A full dump replaces data in those tables. If you already ran `passport:install` on Railway, re-importing a dump from local may **overwrite** `oauth_clients` and users — usually what you want for a true clone. If you only want **book-related** data, use a partial dump or export/import specific tables (more manual).

#### 3) Copy `storage/app/public` (covers, Voyager uploads, etc.)

**Recommended (Mac → container, no file-host):** from `waha-darin`, with Railway CLI linked and [native SSH](https://docs.railway.com/cli/ssh) set up:

```bash
./scripts/push-storage-public-to-railway.sh
```

The script streams a tarball over **`ssh`** to **`{instance-id}@ssh.railway.com`** (same mechanism as `railway ssh --native`), then fixes **`www-data`** ownership.

**Manual archive** (if you extract inside an interactive shell instead):

```bash
cd /path/to/waha-darin/storage/app
tar czvf /tmp/storage-public.tgz public
```

Then get that archive **onto the container** and extract under `storage/app` (same layout as local: `.../storage/app/public/...` → `/var/www/html/storage/app/public/...`). Practical options:

- **`railway ssh`** (interactive), then download the tarball from a temporary URL (e.g. upload `storage-public.tgz` to a private bucket or file share and `curl -o /tmp/storage-public.tgz '...'`), then:

  ```bash
  mkdir -p /var/www/html/storage/app && cd /var/www/html/storage/app && tar xzvf /tmp/storage-public.tgz
  ```

- Or use **Railway’s MySQL TCP proxy** together with GUI tools; for **files**, a **volume** on `/var/www/html/storage/app/public` plus a one-time copy is often easiest.

Afterwards, confirm **`APP_URL`** on the web service is your real Railway HTTPS URL so `Storage::url(...)` and book `cover_photo` URLs stay correct.

#### 4) Keeping files across redeploys

Without a **volume**, redeploying wipes anything you copied into `storage/`. For a long-lived preview, add a Railway volume mounted at **`/var/www/html/storage/app/public`** (or broader `/var/www/html/storage`).

## Render + Neon (PostgreSQL) + static covers in `public/media`

This stack matches **Render free web** (ephemeral disk) and **Neon** (free Postgres, no card): book and author images that are **fixed** live under **`public/media/covers`** and **`public/media/authors`** in Git, so the Docker image serves them without **`storage:link`** or a volume. The API still exposes **`cover_photo`** / **`avatar_photo`**; [`PublicStorageUrl`](../app/Support/PublicStorageUrl.php) uses **`asset()`** for any DB path that starts with **`media/`**, and keeps **`/storage/...`** behavior for legacy rows and Voyager uploads.

### Render service

| Setting | Value |
|--------|--------|
| **Root directory** | For GitHub repo **Dar-books** (`khaledAIVR/Dar-books`): set **`waha-darin`** (the Laravel app is inside that folder, not at the repo root) |
| **Environment** | **Docker** (same [`Dockerfile`](../Dockerfile): Nuxt `generate` → `public/_nuxt`, PHP Apache, Laravel) |
| **Build / start** | Image `ENTRYPOINT` runs [`docker/entrypoint-preview.sh`](../docker/entrypoint-preview.sh) (Passport keys, `storage:link` for anything still on the public disk) |

### Environment variables (Render)

Use your service’s public HTTPS origin everywhere the app builds links (SPA, API, verification emails, password reset). Replace the placeholder with the URL Render shows for your service (no trailing slash).

| Variable | Example / notes |
|----------|-----------------|
| **`APP_URL`** | `https://your-service-name.onrender.com` |
| **`CLIENT_URL`** | Same as **`APP_URL`** (single-origin Nuxt + Laravel in one container) |
| **`ASSET_URL`** | Usually same as **`APP_URL`** if you see mixed-content warnings for CSS/JS |
| **`APP_ENV`** | `production` |
| **`APP_DEBUG`** | `false` |
| **`APP_KEY`** | `php artisan key:generate --show` locally, paste into Render |
| **`JWT_SECRET`** | Long random string (API auth) |
| **`DATABASE_URL`** | Neon PostgreSQL URL, e.g. `postgresql://…?sslmode=require` |
| **`DB_CONNECTION`** | `pgsql` |
| **`DB_SSLMODE`** | `require` if not already in `DATABASE_URL` |
| **`RUN_MIGRATIONS_ON_BOOT`** | `true` (default): [`entrypoint-preview.sh`](../docker/entrypoint-preview.sh) runs **`php artisan migrate --force`** when **`APP_ENV=production`** |
| **`SKIP_EMAIL_VERIFICATION`** | Omit or **`false`** for real behaviour: verification emails, password reset, and registration must work. Do **not** set `true` on public production. |
| **`MAIL_MAILER`** | `smtp` |
| **`MAIL_DRIVER`** | `smtp` (Laravel 7 reads this too) |
| **`MAIL_HOST`** | `smtp.sendgrid.net` (or your provider) |
| **`MAIL_PORT`** | `587` |
| **`MAIL_USERNAME`** | `apikey` (SendGrid) |
| **`MAIL_PASSWORD`** | SendGrid API key — set only in Render **Environment** (secret); **rotate** the key if it was ever pasted into chat or committed |
| **`MAIL_ENCRYPTION`** | `tls` |
| **`MAIL_FROM_ADDRESS`** | Your verified sender (e.g. `info@your-domain.com`) |
| **`MAIL_FROM_NAME`** | e.g. `Dar Books` or `${APP_NAME}` |

Optional blueprint: [`render.yaml`](../../render.yaml) at the **Dar-books** repo root uses **`rootDir: waha-darin`** to match this layout; you still add **`APP_URL`**, **`CLIENT_URL`**, **`APP_KEY`**, **`JWT_SECRET`**, **`DATABASE_URL`**, **`MAIL_PASSWORD`**, etc. in the dashboard.

**HTTPS:** [`AppServiceProvider`](../app/Providers/AppServiceProvider.php) calls **`URL::forceScheme('https')`** in production so generated URLs match Render’s TLS termination.

**Admin (Voyager):** same web service — open **`https://your-service.onrender.com/admin`** after migrations. Create an admin user locally or via **`php artisan voyager:admin`** in a Render shell if you have no admin yet. Voyager uploads still live under ephemeral `storage/` unless you add object storage or a disk.

After first deploy (migrations run on boot), run **once** in a **Render shell** (OAuth clients are not duplicated safely by the entrypoint):

```bash
php artisan passport:install --force
```

### Catalog data: MySQL → Neon (no raw dump)

Avoid importing a **MySQL** dump into **Postgres**. After **`migrate`** on an empty Neon database:

1. From a machine that can reach **both** databases, point **`.env`** default connection at Neon (`DB_CONNECTION=pgsql`, `DATABASE_URL`, …) and set **`MYSQL_SOURCE_*`** (see [`.env.example`](../.env.example) and `mysql_source` in [`config/database.php`](../config/database.php)).
2. Run:

```bash
php artisan data:import-catalog-from-mysql --force
```

That copies **publishers**, **authors**, **categories**, **books**, and **pivot_book_categories** only. It **truncates** those tables plus **`rates`** on Postgres when using **`--force`**. **Users, Voyager tables, borrow orders, carts, subscriptions**, etc. are **not** copied — add seeders, another export path, or extend the command if you need them.

### Moving existing covers from `storage/app/public` into `public/media`

On a machine that has the legacy files under **`storage/app/public`**:

```bash
php artisan media:publish-from-storage
# php artisan media:publish-from-storage --dry-run   # preview
```

This writes **`public/media/covers/{book_id}.ext`** (and **`public/media/authors/{author_id}.ext`**) and updates **`books.image`** / **`authors.avatar`**. New dataset imports from **`books:import-new-dataset`** already place covers under **`public/media/covers/`** (see [`ImportNewDatasetCommand`](../app/Console/Commands/ImportNewDatasetCommand.php)); run **`media:publish-from-storage`** afterward if you want filenames normalized to **`{id}.ext`**.

### Ephemeral disk on Render free

- **Passport key files** under `storage/` are recreated when missing (see [Passport / OAuth keys](#passport--oauth-keys-persistence)); **new deploys** can rotate keys unless you persist `storage/`, so expect **re-login** / re-issued tokens for previews.
- **Voyager uploads** to **`storage/app/public`** are still **lost** on redeploy without external object storage or a paid disk — prefer **`public/media`** for long-lived marketing/catalog images.
- **`/storage/...`** remains for legacy paths and admin uploads; it is **not** required for rows that already store **`media/...`**.

## Render (MySQL only, legacy)

You can still run the same Docker image against **managed MySQL** on Render if you set **`DB_CONNECTION=mysql`** and a MySQL **`DATABASE_URL`**. The runtime image includes **`pdo_pgsql`** only; for MySQL you would need to **add `pdo_mysql`** back to the [`Dockerfile`](../Dockerfile) for that variant.

## Passport / OAuth keys (persistence)

- `storage/oauth-*.key` are **gitignored**. On **first container start**, [`docker/entrypoint-preview.sh`](../docker/entrypoint-preview.sh) runs `php artisan passport:keys --force` if the private key is missing so signing can work.
- **OAuth clients** still require **`php artisan passport:install`** once the database exists (see above). Re-running `passport:install` on every boot can duplicate clients — avoid that; run it manually after migrate.
- If you **mount a volume** on `storage/`, keys and uploads survive redeploys; without a volume, new keys are generated when the filesystem is empty (invalidates old tokens — acceptable for throwaway previews).

## “Free” expectations

- Stable **MySQL + always-on PHP** rarely stays **$0** forever; Railway uses **credits**; Render free web services **sleep** when idle.
- For a **tiny** tester audience, smallest MySQL + one web service is enough.

## Vercel

[`vercel.json`](../vercel.json) is only for hosting the **static** Nuxt build with a **separate** API URL. Ignore it for this **single-URL Docker** workflow.
