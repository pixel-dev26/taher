# Taher Brothers Inventory — Deployment Package

Laravel 11 application. No Node build step is required — the CSS and JS under
`public/css` and `public/js` are plain files, not compiled, so `npm run build`
is not part of deployment. `package.json` is included for reference only.

## What's in this archive

- Full application source (`app/`, `resources/`, `routes/`, `config/`, etc.)
- `database/database.sqlite` — **the client's live data**: 1 admin account,
  152 products, 3 godowns (Howrah, Domjur 1, Domjur 2), and everything
  recorded during setup and testing. This is not a seed/demo file — treat it
  as production data. Take a copy before running any migration.
- `storage/app/public/` — the uploaded company logo and one sample dispatch
  PDF. Real assets, not placeholders.
- `.env.example` — copy this to `.env` and fill in per the steps below.

## What's deliberately excluded

- `vendor/`, `node_modules/` — install fresh with the commands below.
- `.env` — contains a local `APP_KEY` and local debug settings; generate a
  new key for this environment rather than reusing a development one.
- `bootstrap/cache/*.php`, `storage/framework/{cache,sessions,views}/*`,
  `storage/logs/*` — compiled/runtime files, regenerated automatically.
- `public/storage` — a symlink on the dev machine that pointed to an
  absolute local path. Recreate it on the server (step 6).

## Requirements

- PHP 8.2+ with the `pdo_sqlite`, `mbstring`, `fileinfo`, and `gd` (or
  `imagick`) extensions — `gd`/`imagick` is used for the PDF exports.
- Composer 2.
- Write access for the web server user to `storage/` and `bootstrap/cache/`.

## Setup

```bash
# 1. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 2. Environment file
cp .env.example .env
php artisan key:generate

# 3. Edit .env — set at minimum:
#    APP_ENV=production
#    APP_DEBUG=false
#    APP_URL=https://<the real domain>
#    (DB_CONNECTION is already sqlite; DB_DATABASE defaults to
#     database/database.sqlite, which is included and pre-populated)

# 4. Point the web server's document root at public/, or if you can't,
#    at the project root with a rewrite to public/ (see .htaccess in public/).

# 5. Permissions
chmod -R 775 storage bootstrap/cache

# 6. Recreate the storage symlink (logo, dispatch PDFs need this)
php artisan storage:link

# 7. Back up the database, then apply pending migrations. These only add
#    columns (e.g. purchase price on stock receipts); existing records are
#    kept. Never use migrate:fresh — see "Notes on the data".
cp database/database.sqlite database/database-backup-$(date +%Y%m%d).sqlite
php artisan migrate --force
php artisan migrate:status   # everything should now read "Ran"
```

## Updating an existing install

When deploying new code to a server that is already live:

```bash
cp database/database.sqlite database/database-backup-$(date +%Y%m%d).sqlite
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:clear
```

The purchase-price change (Sept 2026) needs this migration before the new
code runs. Without it, saving a stock receipt and opening the Stock screen
will fail.

## Login

Single admin account, already set:

- **Email:** `taherco@hotmail.com`
- **Password:** ask the client directly, or the person who set this up —
  not included in this archive on purpose. It's a live production
  credential, and the client already has it in their user guide.

If you need to verify login works without the real password, reset it
temporarily:

```bash
php artisan tinker
>>> $u = App\Models\User::first();
>>> $u->password = Hash::make('new-password-here');
>>> $u->save();
```

## Verifying it's live

Visit the site, sign in, and check:

- Dashboard loads with the stat cards populated (152 products, 3 godowns).
- **Stock** screen returns results for a search like `GIP`.
- The Taher Brothers logo appears in the sidebar/top bar — confirms
  `storage:link` worked.
- Opening any dispatch and downloading its PDF works — confirms `gd`/`imagick`
  and the storage link are both fine.

## Notes on the data

Nothing about this deployment needs `php artisan migrate:fresh` or
`db:seed` — the included SQLite file already has the schema and the
client's real records. Running a fresh migrate/seed would **wipe the
client's product catalogue and history**, so please don't unless
specifically asked to reset the environment.
