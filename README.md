# PHP Pick 'Em (NFL Pool)

PHP Pick 'Em is a free, open‑source web application for running a weekly NFL pick ’em pool.  
It handles picks, scoring, standings, and automated email notifications for self‑hosted leagues.

This version is a **ground‑up 2025+ rewrite** of Kevin Roth’s original 2013 PHP Pick ’Em, updated for:

- PHP 8.x (tested on 8.1–8.3) with Composer
- MariaDB/MySQL using InnoDB and `utf8mb4`
- Modern security practices (bcrypt passwords, prepared statements, CSRF protection, hardened sessions)
- API‑driven schedules and scores instead of HTML/XML scraping
- A Bootstrap 5 frontend with dark mode and a streamlined admin experience

> Original author: Kevin Roth (2013)  
> Modern rewrite and current maintainer: Paul Combs (2025–2026)

---

## Requirements

- Apache 2.4+ or Nginx
- PHP 7.4+ with `mysqli`, `openssl`, `mbstring` extensions
- MariaDB 10.x or MySQL 5.7+
- Composer

---

## Screenshots

### Installer
| Step 1 — Environment | Step 2 — Database | Step 3 — Complete | Step 4 — Schedule |
|---|---|---|---|
| ![Install Step 1](images/README/install-step1.png) | ![Install Step 2](images/README/install-step2.png) | ![Install Step 3](images/README/install-step3.png) | ![Install Step 4](images/README/install-step4.png) |

### Application
| Login | Admin Dashboard |
|---|---|
| ![Login](images/README/login.png) | ![Admin Dashboard](images/README/admin-dashboard.png) |

| Results | Standings |
|---|---|
| ![Results](images/README/results.png) | ![Standings](images/README/standings.png) |

| Schedules | Scores |
|---|---|
| ![Schedules](images/README/schedules.png) | ![Scores](images/README/scores.png) |

| Manage Users | Reset Tables |
|---|---|
| ![Manage Users](images/README/manage-users.png) | ![Reset Tables](images/README/reset-tables.png) |

---

## Quick Install

1. **Copy files to your web root**
   ```bash
   sudo cp -r phppickem/ /var/www/html/yoursite/
   ```

2. **Set ownership and permissions**
   Replace `apache` with `www-data` on Debian/Ubuntu.
   ```bash
   sudo chown -R apache:apache /var/www/html/yoursite/
   sudo find /var/www/html/yoursite/ -type d -exec chmod 755 {} \;
   sudo find /var/www/html/yoursite/ -type f -exec chmod 644 {} \;
   ```
   The installer needs to write to `includes/config.php` to save your site settings.
   Make it writable before running the installer, then lock it down after:
   ```bash
   # before install
   sudo chmod 664 /var/www/html/yoursite/includes/config.php

   # after install
   sudo chmod 644 /var/www/html/yoursite/includes/config.php
   ```

3. **Install PHP dependencies**
   ```bash
   cd /var/www/html/yoursite/
   composer install --no-dev
   ```

4. **Create the environment file** (outside the web root)
   ```bash
   sudo mkdir -p /home/secure
   sudo nano /home/secure/env.php
   ```
   Paste and fill in your values:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'dbuser');
   define('DB_PASS', 'dbpass');
   define('DB_NAME', 'nfl');
   define('BALLDONTLIE_API_KEY', 'your-key-here'); // optional — needed for score updates
   ?>
   ```
   Set ownership so only the web server can read it:
   ```bash
   # RHEL / AlmaLinux / Rocky
   sudo chown root:apache /home/secure/env.php
   sudo chmod 640 /home/secure/env.php

   # Debian / Ubuntu
   sudo chown root:www-data /home/secure/env.php
   sudo chmod 640 /home/secure/env.php
   ```

5. **Create the database**
   ```sql
   CREATE DATABASE nfl;
   CREATE USER 'dbuser'@'localhost' IDENTIFIED BY 'dbpass';
   GRANT ALL PRIVILEGES ON nfl.* TO 'dbuser'@'localhost';
   FLUSH PRIVILEGES;
   ```

6. **Run the installer**
   ```
   http://your-domain/install/
   ```
   The installer walks through env.php setup, imports the schema, sets your admin password,
   and writes `SITE_URL`, `SEASON_YEAR`, and `ALLOW_SIGNUP` to `config.php`.

7. **Import the NFL schedule** (Step 4 of the installer)
   The installer detects any `nfl_schedule_YYYY.sql` file in the `install/` folder and
   offers it pre-selected. You can also upload your own `.sql` or `.csv` file.
   To generate a fresh schedule from the API:
   ```bash
   cd docs/
   php buildSchedule.php 2025
   ```
   Then place the generated `nfl_schedule_2025.sql` in the `install/` folder before running Step 4,
   or import it later via **Admin → Import Schedule**.

8. **Lock down config.php and delete the installer**
   ```bash
   sudo chmod 644 /var/www/html/yoursite/includes/config.php
   rm -rf /var/www/html/yoursite/install/
   ```

---

## Admin Login

- URL: `/login.php`
- Default username: `admin`
- Password: set during installation

---

## Apache vhost (port 80)

Create `/etc/httpd/conf.d/phppickem.conf`:

```
<VirtualHost *:80>
    ServerName nfl.example.com
    DocumentRoot /var/www/html/nfl.example.com

    <Directory /var/www/html/nfl.example.com>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
</VirtualHost>
```

Reload Apache:
```
sudo systemctl reload httpd
```

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Images or redirects broken | `SITE_URL` must match your actual scheme + host exactly |
| DB connection failed | Verify `/home/secure/env.php` values and DB user grants |
| Login loops | Check PHP session permissions; never mix IP/hostname in the same browser |
| Email not sending | Verify `SMTP_*` constants in `config.php` and test with a CLI mailer |
| Score updates fail | Confirm `BALLDONTLIE_API_KEY` is set and valid in `env.php` |

---

## Compatibility Notice

This version is **not database-compatible** with the original PHP Pick 'Em (2013–2015). Do not attempt to migrate an existing installation — a fresh install is required. Specific incompatibilities include:

- **New tables** — `nflp_teams`, `nflp_divisions`, `nflp_picksummary`, and `nflp_email_templates` did not exist in the original schema
- **Storage engine** — all tables are `InnoDB` (original used `MyISAM`)
- **Character set** — `utf8mb4 COLLATE utf8mb4_unicode_ci` throughout (original used `utf8` or `latin1`)
- **Team abbreviations** — updated to match the current BallDontLie/ESPN standard; several codes differ from what was used 11 years ago
- **Dropped tables** — `nflp_comments` and related data are gone entirely
- **Password storage** — bcrypt replaces the original triple-DES scheme; existing password hashes cannot be carried over

**Start fresh.** Run the installer, import the current season schedule, and re-add your users.

---

## Changes vs. the Original (2013–2015)

This version is a ground-up modernization of Kevin Roth's original PHP Pick 'Em codebase.
The original ran on PHP 4/5 with MySQL 4, stored passwords with a custom mcrypt triple-DES scheme,
and fetched scores by scraping NFL.com XML. The changes below cover every major area.

### Security

- **Passwords** — replaced custom mcrypt triple-DES encryption with PHP-native `password_hash()` / `password_verify()` (bcrypt). The old `phpFreaksCrypto` class and `salt` column are gone.
- **Prepared statements** — every query that touches user input now uses `$stmt->bind_param()`. Raw string concatenation in SQL (original `entry_form.php`, `schedule_edit.php`, etc.) has been fully removed.
- **CSRF protection** — `hash_equals()` token validation on every state-changing form: picks, score entry, schedule edits, email sends, admin resets.
- **Session fixation** — `session_regenerate_id(true)` called on successful login.
- **Session cookie flags** — `httponly`, `samesite=Lax`, and `secure` (when on HTTPS).
- **Output escaping** — `htmlspecialchars()` applied to all user-controlled output; `$_SERVER['REQUEST_URI']` is escaped where it appeared raw in the original.
- **Credentials out of web root** — DB password and API key live in `/home/secure/env.php`, never inside the project directory.
- **`.htaccess` hardening** — security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`), directory listing off, `vendor/` and `install/` blocked from web access.
- **Atomic picks** — pick submission runs inside a DB transaction (`begin_transaction` / `commit` / `rollback`), eliminating partial-save race conditions.

### Database

- **Engine** — all tables converted from `MyISAM` to `InnoDB` (transactional, FK-capable).
- **Charset** — `utf8` → `utf8mb4 COLLATE utf8mb4_unicode_ci` (full Unicode + emoji support; compatible with MariaDB and MySQL 5.7+).
- **New `nflp_teams` table** — 32 teams with BallDontLie/ESPN-compatible abbreviations, city, name, and division FK. Replaces hardcoded team arrays scattered through the original code.
- **New `nflp_divisions` table** — AFC/NFC × North/South/East/West lookup.
- **New `nflp_picksummary` table** — per-user, per-week metadata: `showPicks` flag and `tieBreakerPoints`.
- **`nflp_email_templates`** — simplified schema (dropped redundant `default_subject`/`default_message` columns); added `SEASON_KICKOFF` template.
- **Indexes** — `weekNum` index added to `nflp_schedule` (the most-queried column, absent in the original).

### Email

- **Symfony Mailer** replaces the bundled PHPMailer 2.x classes. Managed via Composer.
- New `EmailHelper` abstraction (`includes/email_helper.php`) centralizes SMTP config, error logging, and HTML/plain-text construction.
- SMTP settings (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`) consolidated in `config.php`.
- **4 email templates** (up from 3): `WEEKLY_PICKS_REMINDER`, `WEEKLY_RESULTS_REMINDER`, `FINAL_RESULTS`, `SEASON_KICKOFF`.
- `FINAL_RESULTS` correctly references the final week of the season (not `currentWeek - 1`).
- `SEASON_KICKOFF` hardcodes Week 1 for `{first_game}` so it always shows a September date.

### Schedule & Scores

- **Schedule source** — `buildSchedule.php` (moved to `docs/`) now targets the **BallDontLie NFL API** instead of the ESPN two-step feed. API key loaded from `env.php`. Outputs a ready-to-import SQL file.
- **Score source** — `getBallDontLieScores.php` replaces `getHtmlScores.php`. Scores are fetched via BallDontLie API (Bearer token auth) instead of NFL.com XML scraping. No manual team-code mapping needed — DB abbreviations match the API directly.
- **Web-based schedule importer** — `schedule_import.php` accepts `.sql` or `.csv` uploads, validates team IDs against `nflp_teams`, offers a dry-run preview, and executes with CSRF protection. Also integrated into the installer as Step 4 — auto-detects bundled `nfl_schedule_YYYY.sql` files.

### Admin

- **Admin dashboard** — replaced the original plain list with a live stat strip (active players, picks submitted, scores entered this week) and action cards. Detects off-season automatically.
- **Table reset utility** (`admin_reset_tables.php`) — grouped by purpose: **New Season Reset** (picks, picksummary, schedule) or **Full Reinstall** (users — unlocks the installer). Dry-run preview, requires typing RESET to confirm.
- **Schedule importer** (`schedule_import.php`) — bulk import with validation and preview.
- **Enhanced user management** (`users.php`, `user_edit.php`) — status toggle, password override, admin flag.
- **Email template editor** (`email_templates.php`) — in-app editing with CSRF protection and live preview variables.
- **CLI utilities** (`docs/cli_admin_password_reset.php`, `docs/cli_gen_password.php`) — server-side tools for password operations without a browser.

### Frontend / UI

- **Bootstrap 5.3.3** throughout (CDN). All Bootstrap 3 markup removed (`form-group`, `form-row`, `thead-light`, `mr-*`, `data-toggle`, etc.).
- **Single CSS file** (`css/site.css`) replaces 6+ original files (Bootstrap 3 local, jQuery UI, countdown, timepicker, SASS partials).
- **Dark mode** — full dark/light theme toggle stored in a cookie; respects OS `prefers-color-scheme` on first visit. All Bootstrap CSS variables overridden cleanly (`--bs-body-color`, card variables, etc.).
- **Typography** — Oswald (headings) + Mulish (body) via Google Fonts; warm off-white / charcoal palette with burnt-orange accent.
- **No local JS bundles** — jQuery, jQuery UI, Modernizr, SVGeezy, countdown, timepicker, CBRTE all removed. Replaced with Bootstrap 5 utilities and vanilla JS where needed.
- **TinyMCE 6** for the email template editor (CDN); adapts to dark/light theme automatically.
- **Standings** — "Show weeks" dropdown replaced with segmented button group (Last 5 / Last 10 / All).
- **Rules page** — redesigned as a 2×2 icon card grid.
- **Footer** — rebuilt to match navbar aesthetic: quick-links, copyright, license link, back-to-top.
- **Password reset** — fixed floating label overlap, missing Font Awesome, broken form structure from the original.

### New Pages

| Page | Description |
|---|---|
| `teams.php` | Team directory grouped by conference and division |
| `donate.php` | PayPal donation page with trust indicators |
| `license.html` | MIT license display with both copyright holders |
| `schedule_import.php` | Admin bulk schedule import |
| `admin_reset_tables.php` | Admin new-season reset utility |

### Removed

- `getHtmlScores.php` — NFL.com XML scraper (replaced by BallDontLie API)
- `includes/htmlpurifier/` — 300+ file HTML Purifier library (removed with commenting system)
- `includes/classes/class.phpmailer.php` + `class.smtp.php` — replaced by Symfony Mailer
- `includes/classes/class.formvalidation.php` — not carried forward
- `includes/comments.php` + Disqus integration — commenting system removed entirely; `nflp_comments` table dropped
- `js/` folder — all local JS bundles (jQuery, plugins, Bootstrap 3 JS)

### App Name

The original had inconsistent naming (`NFL Pick 'Em`, `PHP Pick'Em`, `PHP Pick'em`).
The canonical name is now **PHP Pick 'Em** everywhere, driven by an `APP_NAME` constant in
`config.php` so a single edit propagates site-wide.

---

## Developer Notes

- `DB_PREFIX` (`nflp_`) is used consistently via constant throughout all PHP files.
  The only place it appears literally is in SQL dump files — by design.
- `SEASON_YEAR` in `config.php` drives the schedule builder and season-related displays.
  `NFL_TOTAL_WEEKS` is currently set to 18; review if playoff/bye-week handling changes.
- `BALLDONTLIE_API_KEY` is optional — the app runs without it, but live score updates
  and the schedule builder both require it.

---

## License

MIT — see `license.html` or `LICENSE`.

Original author: Kevin Roth © 2013
Current maintainer: Paul Combs © 2025
