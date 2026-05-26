# Gur Asees Ayurveda — clinic management

PHP application for patient registration, visits, medicines, payments, courier labels, and reports.

## Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `gd`, `mbstring`, `json`
- MySQL 8.0+ (or MariaDB 10.4+)
- Web server with document root pointing at **`public/`** (recommended)

## Quick start (development)

```bash
cp .env.example .env
# Edit .env with database credentials

php scripts/install_db.php
php scripts/seed_users.php

php -S 0.0.0.0:8080 -t public
```

Open `http://localhost:8080`. Log in as `admin` (default password in `scripts/seed_users.php` — change it immediately). Create manager and receptionist users from **Users** in the app.

## Production deployment

### 1. Web server

Use [`deploy/nginx.conf`](deploy/nginx.conf). It starts as **HTTP-only** so Nginx can run before certificates exist. Certbot then adds SSL paths automatically.

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/gur-asees-ayurveda.conf
sudo ln -s /etc/nginx/sites-available/gur-asees-ayurveda.conf /etc/nginx/sites-enabled/
sudo mkdir -p /var/www/letsencrypt
sudo nginx -t && sudo systemctl reload nginx
```

**Let's Encrypt (recommended — Certbot edits the config and inserts certificate paths):**

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d guraseesayurveda.com -d www.guraseesayurveda.com
```

Certbot will add a `listen 443 ssl` server block with `ssl_certificate` / `ssl_certificate_key` under `/etc/letsencrypt/live/...`, enable HTTPS redirect on port 80, and set up auto-renewal.

Set `.env`: `APP_URL=https://guraseesayurveda.com`

Apache: set `DocumentRoot` to `public/` or use the project root `.htaccess`.

### 2. Environment

```bash
cp .env.example .env
```

| Variable | Production |
|----------|------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Full HTTPS URL, e.g. `https://clinic.example.com` |
| `DB_*` | Production database credentials |
| `LOG_PATH` | Optional; defaults to `storage/logs/php.log` |

Never commit `.env`. Ensure `storage/logs` and `storage/cache` are writable by the web server user.

### 3. Database (fresh install)

```bash
php scripts/install_db.php
php scripts/seed_users.php
```

`sql/schema.sql` is the full schema. Run `install_db.php` on a new database only. Use `seed_users.php` once to create the **admin** account, then add other staff from the app.

### 4. Production checklist

- [ ] Document root is `public/`
- [ ] `APP_DEBUG=false`, explicit `APP_URL` over HTTPS
- [ ] Strong database password; dedicated DB user with minimal privileges
- [ ] Default admin password changed after first login
- [ ] `storage/logs` monitored; rotate log files
- [ ] TLS certificate installed (HSTS is sent when HTTPS and not `APP_ENV=local`)
- [ ] Backups for database
- [ ] PHP `opcache` enabled in production

## Project layout

| Path | Purpose |
|------|---------|
| `public/` | Web entrypoints only |
| `app/` | Config, models, helpers |
| `views/` | PHP templates |
| `sql/schema.sql` | Full database schema |
| `scripts/` | CLI install and seed |
| `storage/` | Logs and cache (not web-accessible) |

## Security notes

- CSRF tokens on all POST forms; session cookies are `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS
- Login rate limiting is per username + IP in `storage/cache/login/`
- Deactivated users are logged out on the next request (active status checked at least every 90 seconds)
