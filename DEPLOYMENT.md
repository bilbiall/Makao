# Deploying Renty (shared/cPanel hosting)

This app is designed to run on ordinary shared/cPanel hosting - nothing here requires
Redis, Supervisor, or a persistent Node process. Sessions, cache, and the queue all use
the `database` driver, and scheduled work (auto-invoicing, trial expiry) runs off a
single cron entry.

## 1. Prerequisites on the host

- PHP 8.2+ with the extensions Laravel/Filament need: `mbstring`, `openssl`, `pdo_mysql`,
  `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, **and `intl`** (Filament's
  table components use it for number formatting - without it, any resource list page
  500s with "The intl PHP extension is required"). Most cPanel PHP selectors enable
  `intl` by default; double check under MultiPHP INI Editor if you hit that error.
- A MySQL database + user (cPanel: MySQL Databases).
- Composer and Node/npm are only needed **locally** to build the app before upload -
  they don't need to exist on the server itself.

## 2. Build locally, then upload

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build          # compiles resources/css + resources/js into public/build
```

Upload the whole project **except** `node_modules/` and `.git/`. `vendor/` and
`public/build/` must be included since the server has no Composer/Node.

## 3. Document root

cPanel typically serves from a domain's root folder, but Laravel's actual web root is
`public/`. Two common approaches:

- **Preferred**: point the domain/subdomain's document root at the project's `public/`
  folder directly (cPanel > Domains > set document root).
- **Alternative** (if you can't change the document root): put the project one level
  above `public_html`, then copy `public/index.php` into `public_html/index.php` and
  edit its two `require`/`bootstrap` paths to point at the real `../renty/vendor/...`
  and `../renty/bootstrap/app.php` locations. Copy `public/build`, `public/images`,
  `public/css`, `public/js`, `public/vendor` into `public_html/` alongside it.

## 4. Environment

Copy `.env.example` to `.env` on the server and fill in real values (DB credentials,
`APP_URL`, mail). Then:

```bash
php artisan key:generate
php artisan migrate --force
```

M-Pesa and Pesapal credentials, SMS gateway overrides, and email templates are **not**
set via `.env` - they're configured through the app itself (log in as an admin/landlord
→ Settings) and stored in the `app_settings` table. See `MPESA_INTEGRATION.md` and
`PESAPAL_INTEGRATION.md` for the specifics of each gateway.

## 5. Seed data

- Never run `DemoNairobiSeeder` against a real customer's database - it's for sales
  demos only. Run it against a separate demo database/subdomain if you want a live
  showcase environment: `php artisan db:seed --class="Database\Seeders\DemoNairobiSeeder"`.
- For a genuinely fresh production deploy, `php artisan migrate --force` is enough -
  the first real landlord account is created through the `/signup` flow, and a
  superadmin account should be created once via `php artisan tinker` (there's no
  public UI to create one, by design):
  ```php
  \App\Models\User::create([
      'name' => 'Your Name', 'email' => 'you@yourdomain.com',
      'password' => \Illuminate\Support\Facades\Hash::make('a-strong-password'),
      'role' => 'superadmin',
  ]);
  ```
- Before real landlords can sign up, create at least one `Package` from the superadmin
  panel (`/superadmin/packages`) - the `/signup` and `/pricing` pages show whatever
  packages exist with `is_active = true`, and gracefully show an empty state if none do.

## 6. Cron (required for scheduled work)

Add one cron entry in cPanel (Cron Jobs), running every minute:

```
* * * * * php /home/youruser/path-to-project/artisan schedule:run >> /dev/null 2>&1
```

This single entry drives everything scheduled in `routes/console.php`:
- `app:send-auto-invoices` (daily, if enabled in Settings)
- `app:expire-trials` (daily - flips overdue landlord subscriptions to `expired`)

No `queue:work` daemon is required since `QUEUE_CONNECTION=database` processes jobs
synchronously enough for this app's actual usage (no jobs are currently dispatched to
the queue elsewhere in the codebase) - if that changes later, add a second cron entry
running `php artisan queue:work --stop-when-empty` on a short interval instead of a
long-running `queue:work` daemon, which most shared hosts don't allow anyway.

## 7. Post-deploy checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production` in `.env`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] HTTPS enabled (cPanel AutoSSL) - required for real M-Pesa/Pesapal callbacks
- [ ] At least one active `Package` exists before advertising `/signup`
- [ ] One superadmin account created (see step 5)
- [ ] Cron entry added (step 6)
- [ ] Test the signup flow end-to-end on the real domain (a trial account should land
      in its own `/admin` dashboard with zero cross-visibility into any other landlord)
