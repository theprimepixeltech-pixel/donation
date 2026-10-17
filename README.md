# The Hope Foundation

Multipage PHP 8.3+ application with SQLite persistence, public pages, member and donor accounts, and a protected admin panel. The original standalone `index.html` sample has been removed. Prefer setting the web root to **public/**. A protected repository-root entry point is also included for Hostinger direct Git deployments with Apache/LiteSpeed rewrite support.

## Install and run

Requires PHP 8.3+, PDO SQLite, mbstring, DOM, fileinfo and Composer.

```sh
composer install --no-dev --prefer-dist
php -S 0.0.0.0:8080 -t public public/router.php
```

With Docker (development):

```sh
# First install the locked dependencies; skip if composer install already ran.
docker run --rm -v "$PWD":/app -w /app composer:2 install --no-dev --prefer-dist
docker build -t hope-foundation .
docker run --rm -p 8080:8080 -e HOPE_URL=http://localhost:8080 -v hope-data:/app/storage hope-foundation
```

The built-in PHP server is for development. For production use PHP-FPM with Nginx or Apache, HTTPS, backups, monitored error logs, and restrictive filesystem permissions. Point DocumentRoot at `public/`. Apache requires mod_rewrite and AllowOverride; Nginx should use `try_files $uri /index.php?$query_string` and pass PHP only to a configured PHP-FPM service. On servers where you control the document root, use `public/`. Repository-root hosting is supported only on Apache/LiteSpeed with the supplied root `.htaccess` protections active. Keep `storage/`, `.git/`, `vendor/` and `app/` private. The PHP process needs write access only to private storage and its session directory.

## Hostinger / public_html hosting

Use the safe split-directory upload package described in [Hostinger deployment](docs/HOSTINGER.md). It includes Composer dependencies and keeps private files outside `public_html`. For direct Git deployment into `public_html`, the root `index.php` and `.htaccess` must both be deployed. The rewrite rules deny access to private directories; verify these protections after deployment. See the direct deployment section of the hosting guide.

## Create your administrator

There is no default password or public setup endpoint. Create the first admin on the server:

```sh
php bin/admin.php admin@your-foundation.org 'Foundation Administrator'
```

The command prompts for a 12–72 character password without echoing it. Then sign in at `/login`. It can also reset an existing admin password. Docker users can run `docker exec -it CONTAINER php bin/admin.php EMAIL 'Admin Name'`. Administrators should verify identity before helping users recover accounts; automated email recovery is not configured.

## Working modules

- Public pages: home, about, work, programmes/initiatives, impact stories, events/media, gallery, certificates, CSR, volunteering/careers, contact, privacy and donor wall.
- Persistent enquiries and applications, including volunteer/internship/career and partnership interest, available in the admin inbox.
- Account registration, password-hashed login, session expiry, CSRF checks, login throttling and server-side validation.
- Membership application and admin approval/rejection/renewal, unique ID, validity and review notes.
- Approved members can download generated PDF ID cards with verification QR codes and membership certificates.
- Public Member ID/QR verification exposes status and validity, not contact details.
- Donor accounts and unpaid pledges with purpose, donor details, declaration and display preferences.
- Admin dashboard, member review, donor register, enquiry status, published/draft events/news/stories/initiatives, certificate/report/image uploads, site details and audit log.
- Admin CSV exports (Excel compatible, formula-escaped) and PDF reports.
- Gallery and certificate downloads are served from private storage with publication checks; accepted uploads are MIME-checked PDF/JPEG/PNG files up to 5 MB.

Public pages and core account workflows work without Composer dependencies. Install the locked dependencies to enable PDF and QR documents; a missing PDF dependency does not take the entire website offline.

## Payments and remaining launch requirements

**Payments are disabled at the owner’s request.** Pledges never count as paid donations and do not generate receipts. No real or test payment success can be triggered through this website. Payment status reconciliation, automatically numbered payment receipts, recurring mandates and email/WhatsApp delivery require a selected provider and integration. Receipt download permissions are implemented, but the application does not issue receipts without a future verified payment flow. PAN is intentionally not collected for unpaid pledges.

80G wording remains disabled. Verify applicable 12A/80G approvals before enabling tax claims. Foreign contributions are rejected by the domestic pledge workflow; FCRA eligibility and a separate process must be established before accepting them. Membership documents are generated from approved records; no forged official signature is used.

Before launch, enter actual organisation contact and leadership information in Admin → Settings, publish verified certificates and approved content, confirm the privacy contact/retention policy, set `HOPE_URL` to the HTTPS public origin (used in QR codes), create admin accounts and back up the SQLite database and uploads. The website does not invent impact figures, official approvals, or contact details. Essential authentication cookies are used; no third-party analytics is installed.

## Structure

```
app/
  config/site.php       # environment-based settings
  bootstrap.php         # sessions, headers and request handling
  schema.sql            # idempotent SQLite schema
  database.php          # prepared statements and settings
  actions.php           # authenticated form workflows
  services.php          # uploads, PDFs, QR, reports and pledges
  content.php           # public route metadata
  views/                # shared templates, account and admin views
bin/admin.php           # private admin provisioning CLI
public/                 # preferred web document root
storage/                # private database and uploads (gitignored)
tests/integration.py    # HTTP workflow and access-control tests
```

Environment values must be exported into the PHP process; `.env.example` documents them and no `.env` loader is installed. `HOPE_STORAGE` optionally changes the private storage directory; `HOPE_DB` optionally overrides the database path. Back up the database consistently using SQLite backup tooling, together with uploaded files.

## Checks

`tests/hosting.py` checks the repository-root Apache layout at port 8081, including private-file denial.

```sh
find app public bin -name '*.php' -exec php -l {} \;
# Against a fresh isolated test server configured as described in tests/integration.py:
python3 tests/integration.py
```

In restricted cloud networks where GitHub archive downloads are blocked, Composer `--prefer-source` can install the same locked commits through Git. Configure the container’s Composer/Git CA bundle with the environment’s trusted CA file if required; never disable TLS verification. The optional dependency advisory audit could not run in the onboarding environment because `packagist.org` was blocked by its network proxy. Run `composer audit --locked` before production deployment.
