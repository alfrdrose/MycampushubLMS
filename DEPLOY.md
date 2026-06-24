# Deploying My Campus LMS (free, on Render)

This app is a **Laravel (PHP) monolith**. It deploys as a single Docker web
service on [Render](https://render.com) with a **bundled SQLite database** —
no separate database service required. Demo data is re-seeded automatically on
every deploy, so the demo is always clean and populated.

> **Why not Vercel?** Vercel is built for Node.js / static frontends. This app
> renders HTML on the server with PHP + Blade and needs persistent file storage
> and a database. Render (or Railway / Fly.io / Laravel Cloud) runs the whole
> thing as one service, which is what a Laravel app needs.

---

## What's already set up

- `Dockerfile` — builds PHP 8.4 + Node assets and runs the app.
- `docker/start.sh` — creates the SQLite DB, runs `migrate:fresh --seed`, caches
  config, and serves the app on Render's `$PORT`.
- `render.yaml` — Render Blueprint with all production env vars.
- `.dockerignore` — keeps your local `vendor/`, `node_modules/`, and `.env`
  (secrets!) out of the image.

## Demo login credentials (after deploy)

| Role    | Email                | Password    |
| ------- | -------------------- | ----------- |
| Admin   | `admin@email.com`    | `123123123` |
| Teacher | `teacher@gmail.com`  | `123123123` |
| Student | `student@gmail.com`  | `123123123` |

---

## Step-by-step

### 1. Generate an APP_KEY (one time)

```bash
php artisan key:generate --show
```

Copy the whole `base64:...` string. You'll paste it into Render in step 4.

### 2. Push the project to GitHub

```bash
git add Dockerfile docker/ render.yaml .dockerignore DEPLOY.md .env.example
git commit -m "Add free Render deploy config (Docker + bundled SQLite)"
git push
```

> `.env` is gitignored and never pushed — good. Your secrets stay local.

### 3. Create the service on Render

1. Go to <https://dashboard.render.com> and sign up (GitHub login is easiest). No
   credit card required for the free tier.
2. Click **New +** → **Blueprint**.
3. Connect your GitHub account and pick this repository.
4. Render reads `render.yaml` and proposes a service named **mycampus-lms**.
   Click **Apply**.

### 4. Set the APP_KEY

The blueprint marks `APP_KEY` as "sync: false", so Render will ask for it.

1. Open the **mycampus-lms** service → **Environment**.
2. Add/confirm `APP_KEY` = the `base64:...` value from step 1.
3. (Optional) Set `APP_URL` to your real Render URL once you know it
   (e.g. `https://mycampus-lms.onrender.com`).
4. Save — Render redeploys automatically.

### 5. Wait for the build

First build takes ~5–10 minutes (it compiles assets and installs PHP deps).
When the log shows `php artisan serve` running, open the service URL. You should
land on the login page. Log in with the admin credentials above.

---

## Keeping it awake (optional)

Render's free tier sleeps after ~15 min idle; the next visitor waits ~30–50s
while it wakes. You don't have to do anything — it wakes on its own. To avoid
even that cold start:

1. Sign up free at <https://uptimerobot.com>.
2. **Add New Monitor** → type **HTTP(s)** → paste your Render URL →
   interval **5 minutes** → Create.

That pings the site every 5 minutes so it never sleeps.

---

## Notes & limitations (fine for a portfolio demo)

- **Data resets on every deploy** (and whenever Render restarts the instance),
  because the SQLite file lives inside the ephemeral container. This is
  intentional — the demo is always clean and re-seeded. For persistent data,
  switch `DB_CONNECTION` to a managed Postgres/MySQL and remove `migrate:fresh`
  from `docker/start.sh` (use `migrate` instead).
- **Email is disabled** (`MAIL_MAILER=log`) so nothing actually sends. Any
  "email" is written to the log instead.
- **`APP_DEBUG=false`** in production — error pages are generic, not stack traces.

## Security reminder

The original `.env` contained a live Gmail app password. **Revoke it** in the
Google account it belongs to (Google Account → Security → App passwords), since
it was stored in plaintext. The deploy does not use it.
