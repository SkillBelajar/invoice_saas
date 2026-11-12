# Invoice SaaS — Piutang & Penagihan

Aplikasi SaaS invoicing untuk owner/admin usaha. Slice 1: infrastruktur dasar.

**Versi: 0.4**

## Stack

Laravel 13 · Octane (Swoole) · Inertia + Vue 3 · Tailwind CSS v4 · MySQL 8 · Redis 7 · Nginx · Docker Compose

## Jalankan

```bash
cp .env.example .env
docker compose up -d --build
```

Buka http://localhost:8181

**Publik:** https://invoice.pawandigital.id (Cloudflare Tunnel → Nginx :8181)

Container pertama kali start akan generate `APP_KEY` sendiri bila `.env` belum punya. Kalau `app` restart-loop dengan `MissingAppKeyException`, generate manual:

```bash
docker run --rm --entrypoint php -v "$PWD":/var/www/html -w /var/www/html invoice_saas-app \
  artisan key:generate --no-interaction --force
docker compose up -d --force-recreate app
```

## Port

| Service | Host | Container |
|---|---|---|
| web (Nginx) | 8181 | 80 |
| db (MySQL) | 33071 | 3306 |
| redis | 63801 | 6379 |
| app (Octane) | internal | 8000 |

## Frontend

Aset hasil build di-commit ke `public/build` karena host target tidak punya Node dan `public/` di-bind-mount. Setelah mengubah `resources/`, rebuild:

```bash
docker run --rm -v "$PWD":/app -w /app node:22-alpine sh -c "npm install && npm run build"
docker restart invoice_web
```

**Vite dipin ke `^7` + `laravel-vite-plugin` `^2`.** Vite 8 (rolldown) butuh AVX2; host tanpa AVX2 crash `Illegal instruction`. Jangan naikkan tanpa cek `grep -c avx2 /proc/cpuinfo`.

## Struktur

```
app/Http/Middleware/HandleInertiaRequests.php   # share appName + appVersion
config/app.php                                   # 'version' => env('APP_VERSION', '0.4')
resources/js/Pages/Welcome.vue                   # placeholder landing page
resources/views/app.blade.php                    # Inertia root view
docker/nginx/default.conf                        # reverse proxy ke Octane
docker/php/entrypoint.sh                         # key gen, migrate, cache, octane:start
.ai-project-memory.md                            # state proyek & roadmap 9 slice
```

## Perintah harian

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan octane:reload    # setelah ubah kode PHP
docker compose exec app php artisan config:clear
docker compose logs -f app
```

## Roadmap

1. **Setup Arsitektur** — selesai
2. Landing Page
3. Auth
4. Master Data
5. Invoicing Engine
6. PDF
7. Dashboard
8. User Guide
9. Closing
