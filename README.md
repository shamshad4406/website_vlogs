# VlogHub

A multi-role vlogging platform built on Laravel 12: creators upload, a human
reviews every upload, and viewers discover, watch, save and follow.

Built against the *VlogHub Product Blueprint & Market Launch Plan v1.0*.

---

## Running it locally

You need PHP 8.2+, MySQL 8, Composer and Node 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create the database named in `DB_DATABASE`, then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

`php artisan serve` runs it at `http://127.0.0.1:8000`.

### Under XAMPP

With the project sitting in `htdocs`, the application is at:

```
http://localhost/website_vlog/public/
```

The `.htaccess` in the project root redirects `/website_vlog/` to that URL and
blocks direct requests for `.env`, `composer.json` and the framework folders.
It contains a `RewriteBase` that must match the folder name, so change it if
you rename or move the project.

`APP_URL` must include the `/public` part for console-generated links (emails,
sitemaps) to be right.

On a real server, point the virtual host DocumentRoot at `public/` and delete
that file. It is faster, the URLs lose `/public`, and `.env` ends up outside
the web root where it cannot be requested at all.

### The accounts the seeder creates

| Account | Where it comes from |
| --- | --- |
| Administrator | `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env`. If no password is set, one is generated and printed once during seeding. |
| Five demo creators | Printed during seeding; the password is also saved to `DEMO_PASSWORD` in `.env`. |
| Three demo viewers | Same password as the creators. |

The seeder also creates 29 demo vlogs with generated artwork, comments, likes,
follows, view history, a series, a playlist, advertisements and earnings, so
every screen has real data in it.

### Background work

Two processes are needed for the platform to behave correctly:

```bash
php artisan queue:work
```

```bash
php artisan schedule:work
```

On a server, run the queue worker under Supervisor and add one cron entry:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

The schedule publishes vlogs whose scheduled time arrives, rolls up daily
statistics, expires finished ad campaigns, prunes raw view rows after 90 days
and sends the weekly digest.

---

## How it is put together

| Layer | Choice |
| --- | --- |
| Backend | Laravel 12, PHP 8.2 |
| Front end | Blade + Tailwind 3 + Alpine.js, built with Vite. One design system across the public site, the studio and the admin panel. No CDN stylesheets. |
| Database | MySQL 8 (SQLite in tests) |
| Queue, cache, session | Database drivers by default; switch to Redis in production |
| Search | MySQL FULLTEXT with a LIKE fallback |
| Video | Progressive playback out of the box; HLS when FFmpeg is installed |

### Roles

`Admin`, `Creator`, `User`, plus `Moderator`, `Support`, `Finance` and
`Editor` for staff. The `roles`, `permissions` and `role_permissions` tables
drive the admin *Roles and permissions* screen; admins bypass permission
checks entirely.

### The video pipeline

FFmpeg is optional and detected at runtime.

- **Without FFmpeg** (the default): uploads are stored and played back as a
  single progressive file, the duration is read in the browser during upload,
  and a creator-supplied thumbnail is required.
- **With FFmpeg** (set `FFMPEG_PATH` and `FFPROBE_PATH`): the queue job probes
  the real duration, cuts thumbnail candidates and builds 360p–1080p HLS
  renditions. The player loads `hls.js` only when it is needed.

### What the blueprint listed as critical, and where it is handled

| Finding | Where |
| --- | --- |
| C1 — drafts and private vlogs readable by URL | `Vlog::isViewableBy()`, enforced in `VlogController::show`, covered by `tests/Feature/VlogVisibilityTest.php` |
| C2 — raw storage paths, missing storage link | `media_url()` in `app/Helpers/helpers.php`, used by every model accessor |
| C3 — exposed `.env` | Key rotated, `.env.example` rewritten, root `.htaccess` blocks dotfiles and framework folders |
| H1 — no commenting | `CommentController`, `ContentFilter`, moderation queue |
| H2 — a refresh counted as a view | `ViewRecorder`, de-duplicated per visitor per 30 minutes, aggregated daily |
| H3 — uploads straight to disk | Real MIME validation, size and duration limits, queued processing |
| H4 — no way to apply as a creator | `BecomeCreatorController` and the public creator directory |
| H5 — no rate limiting | Named limiters in `AppServiceProvider` on comments, reactions, follows, reports, search, uploads and public forms |
| M1 — slug changed on every edit | Slug frozen after publication; `vlog_slug_history` keeps old links working with a 301 |
| M4 — slow LIKE search | FULLTEXT index plus filters for category, duration, date, creator and place |
| M5 — placeholder content | Local SVG and GD artwork; no external placeholder service |
| M6 — two UI systems | One Tailwind design system everywhere |

---

## Tests

```bash
php artisan test
```

221 tests cover visibility rules, the community loop, the creator workflow,
role access control, security headers and rate limits, every admin and studio
screen, and the public site.

Style: `./vendor/bin/pint`.

---

## Before you launch

1. Set `APP_ENV=production`, `APP_DEBUG=false` and a fresh `APP_KEY`.
2. Serve over HTTPS, set `SESSION_SECURE_COOKIE=true`.
3. Configure real SMTP, plus SPF, DKIM and DMARC for the sending domain.
4. Have a lawyer review the policy pages, which are drafts.
5. Fill in the legal settings, including the grievance officer.
6. Set up daily off-site backups and test a restore.
7. Build caches on deploy: `config:cache`, `route:cache`, `view:cache`.
8. Run the queue worker under Supervisor and the scheduler from cron.

Admin → **System health** checks most of this for you and says what is still
outstanding.
