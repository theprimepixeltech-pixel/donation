# The Hope Foundation

Responsive multipage PHP website with shared templates, mobile navigation, local illustrations, and no third-party frontend dependencies. Requires PHP 8.1+.

## Run locally

```sh
cd /workspace/donation
php -S 0.0.0.0:8080 -t public public/router.php
```

Alternatively, with Docker:

```sh
docker run --rm -p 8080:8080 -v "$PWD":/app -w /app php:8.3-cli php -S 0.0.0.0:8080 -t public public/router.php
```

For Apache, point DocumentRoot at `public/` and enable mod_rewrite and AllowOverride for its .htaccess. For Nginx, route missing public files to `/index.php`. Keep `app/`, `storage/`, `.git/`, and configuration outside the document root. Use HTTPS in production. The PHP built-in server is for development only.

## Structure

- `public/index.php`: allowlisted page routes and entry point
- `public/router.php`: development server router
- `public/assets/`: shared CSS and JavaScript
- `app/bootstrap.php`: session, security headers, escaping, CSRF and demo validation
- `app/config/site.php`: organisation and feature configuration
- `app/content.php`: page metadata and programme content
- `app/views/`: shared header, footer, content, and form templates
- `storage/`: reserved private runtime storage; not publicly served
- `index.html`: original standalone sample, retained for reference; not the PHP entry point

## Pages

Home, About, Our work, Initiatives, Impact, Certificates, CSR/Partnerships, Membership, Get involved, Donate, Events/Media, Contact, Member portal, Donor portal, ID verification, and Admin overview. Unknown routes return HTTP 404.

## Scope

This is a working presentation site, not a completed donation management system. Enquiry and application forms validate name, email, message, and acknowledgement but intentionally do not persist or send information. No credentials are collected. No real impact figures, registration numbers, contact details, or certificates are invented.

Before production, implement a database, role-based authentication, application approval and renewal workflows, payment-provider integration with verified signed webhooks and idempotency, numbered PDF receipts, QR membership verification, digital cards/certificates, consent tracking, email/WhatsApp delivery, donor history, admin CRUD, audit logs, reconciliation and exports. Add applicable privacy and donation policies. Verify all organisational content. Keep 80G wording disabled until applicable approval is valid and handle foreign donations through a separate verified FCRA workflow. Do not turn on a feature flag as a substitute for implementing and validating its backend.

## Verification

```sh
find app public -name '*.php' -exec php -l {} \;
```

Run the local server, request each route, check unknown routes return 404, and exercise form CSRF rejection and valid demo validation. No package installation or database is needed for the current presentation site.
