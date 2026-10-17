# Hostinger deployment

The GitHub repository has its entry point in `public/index.php`. Uploading the whole repository into `public_html` leaves no root index and can produce **403 Forbidden**. Files or directories with restrictive upload permissions can also produce 403. A 503 after this is fixed typically indicates PHP dependencies, extensions, or storage permissions; check the hosting PHP error log.

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

Do not place `app`, `vendor`, `storage`, `.git`, or backups under `public_html`. If the whole repository was previously uploaded there, move those private files outside the public directory as part of deployment. Do not blindly delete files from an existing site.

If your plan cannot store files above `public_html`, ask Hostinger support to point the website document root at the repository’s `public/` directory. Do not expose the whole repository as a workaround. This application requires PHP hosting; static-only hosting cannot run it.

The package's `hope-private/hosting.php` sets the public URL used by QR codes. If the domain changes, update that non-secret setting. Payments remain disabled. Existing live databases/uploads must be backed up and retained when deploying later versions; do not replace an existing `hope-private/storage` with a fresh package copy.

## Troubleshooting

- **403 at `/`**: check `public_html/index.php` exists directly inside the domain’s actual public root and permissions are correct. Check hPanel password protection, access restrictions and the server error log if that does not resolve it.
- **503**: check `hope-private/vendor/autoload.php` exists, PHP/extensions match the requirements, the private directory is readable to PHP, and storage is writable. The error log has the underlying failure; do not enable public PHP error display.
- **404 at `/about` but homepage works**: check `.htaccess` is present and rewrite rules are enabled.
- **403 only at `/admin` after sign-in**: the signed-in account is not an administrator. Create or promote the intended admin using the private CLI command.
