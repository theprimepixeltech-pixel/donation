# Hostinger deployment

The preferred entry point is `public/index.php`. Earlier versions had no repository-root entry point, so deploying the whole repository into `public_html` could produce **403 Forbidden**. The latest version also includes a protected root entry point for direct Git deployments. Restrictive file/directory permissions can independently cause 403. A 503 after this is fixed typically indicates PHP dependencies, extensions, or storage permissions; check the hosting PHP error log.

## Direct Git deployment into public_html

The repository now includes a root `index.php` and root `.htaccess`, so a direct Git deployment can work without moving folders. Deploy the latest `main` commit into the domain’s actual `public_html` (not a nested `public_html/donation` folder). Include hidden files, particularly the root `.htaccess`.

1. In hPanel redeploy the latest GitHub commit. Confirm `public_html/index.php` and `public_html/.htaccess` exist.
2. Select PHP 8.3+ with PDO SQLite, mbstring, DOM and fileinfo.
3. Use 755 for code directories and 644 for code files. Give the account’s PHP process write access to `storage/` (normally owner-only access is sufficient); do not use 777.
4. Visit `/`, `/about`, `/login` and `/assets/css/site.css`.
5. Verify `/app/config/site.php`, `/storage/hope.sqlite`, `/composer.json` and `/.git/config` return **403**, never their contents. If private paths are accessible, stop using repository-root hosting and use the split-directory package or ask the provider to change the document root to `public/`.
6. In Hostinger’s terminal run `composer install --no-dev` from `public_html` to enable PDF and QR downloads. Public pages and account workflows do not require these optional document libraries to render. Create the admin with `php bin/admin.php EMAIL 'Admin Name'`.
7. Export `HOPE_URL` with the HTTPS website origin for QR verification links, or set the non-secret URL in `app/config/site.php` if your plan has no environment configuration.

The root rewrite configuration serves only the front controller and allowlisted static assets. It blocks source, database, dependency, hidden and deployment files. Do not remove these protections. Apache/LiteSpeed must apply `.htaccess`; this fallback is not for Nginx or static hosting. The original sample `index.html` remains removed.

## Recommended: ready-to-upload package

The packaging script includes the locked Composer dependencies and sets regular files to 644 and directories to 755 in the ZIP. No accounts, passwords, databases, uploaded documents or Git metadata are packaged.

```sh
composer install --no-dev
python3 bin/package-hostinger.py --url https://YOUR-DOMAIN
```

On Hostinger:

1. In hPanel select your website → File Manager. Open the domain directory containing `public_html`. You may need the file-manager option that gives access to all account files.
2. Back up existing site files before replacing them. Keep backups outside `public_html`.
3. Upload `dist/hostinger-site.zip` to this **parent directory**, and extract it there. Do not extract the entire archive inside `public_html`.
4. Confirm this exact arrangement:

```
domain-directory/
  public_html/
    index.php
    .htaccess
    assets/
  hope-private/
    app/
    bin/
    public/index.php
    vendor/autoload.php
    storage/uploads/
    hosting.php
```

5. Select PHP **8.3 or newer** in hPanel → PHP Configuration. Enable **pdo_sqlite/sqlite3, mbstring, dom, fileinfo** if the plan lets you manage extensions.
6. Ensure public folders are **755** and public files **644**. The PHP process must be able to write `hope-private/storage/`. On Hostinger PHP normally runs as the account owner; use 700 or 750 for private storage where supported. Do not set 777.
7. Visit `/`, `/about`, and `/login`. The supplied `.htaccess` must be included; File Manager may hide dotfiles unless enabled. If nested routes fail, check that rewrite rules are supported by the selected hosting setup.
8. In the hosting terminal, from the domain directory, create your administrator:

```sh
php hope-private/bin/admin.php admin@your-foundation.org 'Foundation Administrator'
```

If the CLI uses an older PHP version than the website, select the hosting provider’s PHP 8.3+ CLI command. There is no default admin password or public setup page.

For this split-directory package, keep `app`, `vendor`, `storage`, `.git`, and backups outside `public_html`. If the whole repository was previously uploaded there, move those private files outside the public directory as part of deployment. Do not blindly delete files from an existing site.

If your plan cannot store files above `public_html`, ask Hostinger support to point the website document root at the repository’s `public/` directory. Do not expose the whole repository as a workaround. This application requires PHP hosting; static-only hosting cannot run it.

The package's `hope-private/hosting.php` sets the public URL used by QR codes. If the domain changes, update that non-secret setting. Payments remain disabled. Existing live databases/uploads must be backed up and retained when deploying later versions; do not replace an existing `hope-private/storage` with a fresh package copy.

## Troubleshooting

- **403 at `/`**: check `public_html/index.php` exists directly inside the domain’s actual public root and permissions are correct. Check hPanel password protection, access restrictions and the server error log if that does not resolve it.
- **503**: check `hope-private/vendor/autoload.php` exists, PHP/extensions match the requirements, the private directory is readable to PHP, and storage is writable. The error log has the underlying failure; do not enable public PHP error display.
- **404 at `/about` but homepage works**: check `.htaccess` is present and rewrite rules are enabled.
- **403 only at `/admin` after sign-in**: the signed-in account is not an administrator. Create or promote the intended admin using the private CLI command.
