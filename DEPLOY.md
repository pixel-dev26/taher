# Taher Brothers Inventory — Deployment Package

Laravel 11 application. No Node build step is required — the CSS and JS under
`public/css` and `public/js` are plain files, not compiled, so `npm run build`
is not part of deployment. `package.json` is included for reference only.

## What's in this archive

- Full application source (`app/`, `resources/`, `routes/`, `config/`, etc.)
- `database/database.sqlite` — **the client's live data**: the admin account,
  152 products, 3 godowns (Howrah, Domjur 1, Domjur 2), and everything
  recorded during setup and testing. This is not a seed/demo file — treat it
  as production data. Take a backup before running any migration.
- `storage/app/public/logo/` — the uploaded company logo. A real asset, not a
  placeholder. (Dispatch PDFs and challans are generated on demand and are
  never stored on disk.)
- `.env.example` — a production template. Copy it to `.env` and fill in the
  blanks per the steps below.

## What's deliberately excluded

- `vendor/`, `node_modules/` — install fresh with the commands below.
- `.env` — contains a local `APP_KEY` and local debug settings; generate a
  new key for this environment rather than reusing a development one.
- `bootstrap/cache/*.php`, `storage/framework/{cache,sessions,views}/*`,
  `storage/logs/*` — compiled/runtime files, regenerated automatically.
- `public/storage` — a symlink on the dev machine that pointed to an
  absolute local path. Recreate it on the server (step 6).

## Requirements

- **PHP 8.2, 8.3 or 8.4** — not 8.5: the locked `phpoffice/phpspreadsheet`
  release refuses PHP >= 8.5 and `composer install` will abort. (The dev
  machine runs 8.5 only by ignoring that platform check.)
- PHP extensions: `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `gd` (or
  `imagick`), `openssl`, `tokenizer`, `ctype`, `iconv`, `dom`, `libxml`,
  `xml`, `xmlreader`, `xmlwriter`, `simplexml`, `zip`, `zlib`, `session`,
  `filter`. `composer check-platform-reqs` (step 1) lists anything missing.
- Composer 2.
- Write access for the web server user to `storage/` and `bootstrap/cache/`.
- A cron entry for the scheduler (step 8) so nightly backups run.
- HTTPS. The production `.env` marks the session cookie Secure, so the site
  must be served over HTTPS or nobody will be able to stay signed in.

## Setup

```bash
# 1. Check the host first, then install PHP dependencies
composer check-platform-reqs --no-dev
composer install --no-dev --optimize-autoloader
composer audit            # report any newly published advisories

# 2. Environment file
cp .env.example .env
php artisan key:generate

# 3. Edit .env — set at minimum:
#    APP_URL=https://<the real domain>
#    MAIL_HOST / MAIL_PORT / MAIL_USERNAME / MAIL_PASSWORD / MAIL_ENCRYPTION
#    MAIL_FROM_ADDRESS   (the Forgot Password form needs a working mailbox;
#                         until then it tells users to contact the admin)
#    Leave DB_CONNECTION=sqlite and DB_DATABASE unset: it resolves to
#    database/database.sqlite, which is included and pre-populated. If you
#    move the file outside the project tree (recommended on shared hosting),
#    set DB_DATABASE to its absolute path instead.
#    Never set MAIL_MAILER=log on a server — it writes live password-reset
#    links into the log file.

# 4. Point the web server's document root at public/ — this is mandatory.
#    Do NOT point it at the project root: .env, the SQLite database, its
#    backups and the log file would all be downloadable by URL.
#    On shared hosting where the docroot can't be changed, put the whole
#    project one level ABOVE public_html and copy/symlink only the contents
#    of public/ into public_html, then fix the two require paths in
#    public_html/index.php to point at ../<project>/vendor/autoload.php and
#    ../<project>/bootstrap/app.php.

# 5. Permissions
chmod -R 775 storage bootstrap/cache

# 6. Recreate the storage symlink (only the company logo is served from it)
php artisan storage:link

# 7. Sanity-check that the app is talking to the right database, then back it
#    up and apply pending migrations. These only add columns/tables; existing
#    records are kept. Never use migrate:fresh — see "Notes on the data".
php artisan db:show          # the skus table must show ~152 rows
php artisan app:backup-db    # writes storage/app/backups/database-<timestamp>.sqlite
php artisan migrate --force
php artisan migrate:status   # everything should now read "Ran"

# 8. Scheduler (nightly backups). Add to the web server user's crontab:
#    * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Updating an existing install

When deploying new code to a server that is already live:

```bash
php artisan app:backup-db
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:clear
```

The database runs in WAL mode, so a plain `cp` of `database.sqlite` is NOT a
reliable backup (recent writes may still be in the `-wal` file). Always use
`php artisan app:backup-db`, which produces a single consistent file.

## Login

Single admin account, already set. The email address and password are in the
client's user guide — ask the client directly. They are live production
credentials and are not included in this archive on purpose.

New staff accounts are created from the Users screen by the admin; the
temporary password the admin types is only usable once (the user must choose
their own on first sign-in, and it must differ from the temporary one).

If you need to verify login works without the real password, reset it
temporarily (this also signs the account out everywhere):

```bash
php artisan tinker
>>> $u = App\Models\User::where('role', 'admin')->first();
>>> $u->password = Hash::make('new-password-here');
>>> $u->save();
>>> $u->invalidateOtherSessions();
```

## Verifying it's live

Visit the site over HTTPS, sign in, and check:

- Dashboard loads with the stat cards populated (152 products, 3 godowns).
- **Stock** screen returns results for a search like `GIP`.
- The Taher Brothers logo appears in the sidebar/top bar — confirms
  `storage:link` worked.
- Opening any dispatch and downloading its PDF works — confirms `gd`/`imagick`.
- Reports > Stock Ledger > Export Excel downloads a file — confirms `ext-zip`
  and friends.
- `https://<domain>/.env` and `https://<domain>/database/database.sqlite`
  return 404 — confirms the document root is `public/`.
- Five wrong passwords in a row on the login page produce a "too many
  attempts" message — confirms the cache store is working.

## Notes on the data

Nothing about this deployment needs `php artisan migrate:fresh` or
`db:seed` — the included SQLite file already has the schema and the
client's real records. Running a fresh migrate/seed would **wipe the
client's product catalogue and history**, so please don't unless
specifically asked to reset the environment.

The pending migrations add the admin/staff roles, the Activity Log, HSN and
rate fields on dispatch and transfer lines, and the transfer challan fields.
If more than one user account exists when the roles migration runs, only the
oldest active account is promoted to admin — review the Users screen
afterwards.
