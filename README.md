# Nivaro Capital Investment Platform

Nivaro Capital is a dependency-light PHP/MySQL investment platform scaffold built from the supplied functional references. The interface is original: it preserves the workflows shown in the screenshots while redesigning them as a responsive SaaS-style user portal and separate administration console.

## Technology

- HTML5, CSS3, vanilla JavaScript
- PHP with centralized services and reusable layouts
- MySQL schema in `database/schema.sql`
- No frontend frameworks, CSS frameworks, JavaScript frameworks, Laravel, or jQuery

## Implemented Modules

- Public landing page, login, registration
- User dashboard with wallet, earnings, quick actions, activity, and VIP products
- Wallet overview, deposits, withdrawals, bank binding, transactions
- Investment list, investment details, purchase workflow, orders
- Referral center with code copying, three levels, and rebate rules
- Reward redemption and reward history
- Notifications, support tickets, downloads, profile, settings
- Separate administrator console with users, wallets, plans, orders, deposits, withdrawals, transactions, referrals, rewards, announcements, support, reports, CMS, website settings, payment settings, and logs

## Demo Access

User portal:

```text
Email: apex@example.com
Password: password123
```

Admin portal:

```text
Open /admin/index.php?route=login
```

The current implementation uses PHP sessions as a working demo data store so the project can run immediately without database credentials. The MySQL schema is included and mirrors the service boundaries used by the PHP modules.

## Installation

1. Copy the project to a PHP-enabled web server.
2. Import `database/schema.sql` into MySQL if you are setting up the production database.
3. Update `includes/config/app.php` with production database credentials only if you are connecting to a real database.
4. Point the web root at the project directory.
5. Open `index.php?route=home`.

## XAMPP Local Setup

If you already have XAMPP installed, use Apache and the bundled PHP runtime. You do not need a separate PHP installer.

1. Open the XAMPP Control Panel.
2. Start `Apache`.
3. Start `MySQL` only if you want to import or use the database schema.
4. Copy the project folder into `C:\xampp\htdocs\`.
5. Rename the folder if needed so the path is easy to type, for example `C:\xampp\htdocs\investment-website`.
6. Open your browser and visit:

```text
http://localhost/investment-website/index.php?route=home
```

If the browser shows raw PHP or downloads the file, the project is not being served through Apache yet. Do not open `index.php` with `file:///...`.

## Command-Line PHP Check

The README previously assumed `php` was available as a terminal command. On Windows, XAMPP can be installed correctly while PowerShell still says PHP is missing because `C:\xampp\php` is not on PATH.

Check PHP directly with:

```powershell
C:\xampp\php\php.exe -v
```

Expected output:

```text
PHP 8.x.x (cli) ...
```

If you want the shorter `php` command to work in every terminal, add this folder to your Windows PATH:

```text
C:\xampp\php
```

After updating PATH, close and reopen PowerShell, then run:

```powershell
php -v
```

For local testing without Apache, use the XAMPP PHP binary directly:

```bash
C:\xampp\php\php.exe -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/index.php?route=home`.

## Architecture

- `index.php` routes public and user portal requests.
- `admin/index.php` routes administration requests.
- `includes/bootstrap.php` initializes configuration, sessions, helpers, auth, seed data, and services.
- `includes/services/platform.php` centralizes wallet, investment, deposit, withdrawal, referral, reward, support, notification, and admin action logic.
- `includes/layouts` contains reusable public, user, and admin shells.
- `pages/public`, `pages/user`, and `pages/admin` contain module-specific views.
- `assets/css` contains the design system, responsive layout, and admin theme adjustments.
- `assets/js/app.js` provides navigation toggles, copy controls, preset deposit buttons, confirmation prompts, and live filtering.

## Workflow Notes

Financial actions follow the same sequence:

1. Validate request.
2. Apply business rules in `includes/services/platform.php`.
3. Update the session-backed state.
4. Create a transaction where money moves.
5. Add a notification.
6. Record activity.
7. Recalculate dashboard metrics.

In production, the same service functions should be adapted to use PDO transactions against the included MySQL schema.

## Future Extension Points

- Replace session data with repository classes backed by PDO.
- Add role-based permission checks to admin actions.
- Add upload storage validation for payment proofs, CMS images, avatars, and plan images.
- Add scheduled investment profit accrual through cron.
- Add payment gateway/provider integrations.
- Add CSV/Excel report generation.
- Add multi-language and theme preference tables.

## Database / Production Mode (MySQL)

The platform ships in a **demo mode** that stores all state in PHP sessions. To run
with real persistence you must connect a MySQL database and migrate the service layer.

1. **Install the schema.** Either:
   - Visit `install.php` once in a browser (imports `database/schema.sql` and seeds the
     default admin + roles idempotently), or
   - Import manually:
     ```bash
     mysql -u root -p < database/schema.sql
     ```
2. **Point the app at the database** in `includes/config/app.php`, or override per
   environment with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
3. **Enable DB mode** by setting `db_enabled => true` in config, or `DB_ENABLED=true`
   in the environment.

> ⚠️ Do **not** enable `db_enabled` until the wallet / investment / deposit /
> withdrawal / referral services in `includes/services/platform.php` and
> `customer.php` have been migrated from the session store to MySQL and tested
> against a live database. Authentication (`includes/auth_db.php`) is already
> DB-backed, but the rest of the app still reads the session shape.

## Deployment

This is a PHP/MySQL application. **Vercel does not run PHP** and is not a suitable
host. Use a PHP + MySQL host instead.

### Option A — Render (recommended PaaS)
- A `render.yaml` and `Dockerfile` are included. Connect the GitHub repo, Render
  provisions a MySQL database and injects the connection variables automatically.
- After the first deploy, visit `/install.php` once to create the schema, then delete it.

### Option B — Shared hosting (cPanel)
- Upload the project files to `public_html` (or a subfolder).
- In cPanel, create a MySQL database + user and import `database/schema.sql`.
- Set the credentials in `includes/config/app.php`.
- Point your domain at the project directory and open `index.php?route=home`.

### Option C — VPS (Docker)
- `docker build -t nivaro . && docker run -p 80:80 nivaro` (provide a MySQL container
  or external database and the `DB_*` environment variables).

### Pre-launch checklist
- [ ] MySQL wired up (not sessions) for every service, tested live.
- [ ] HTTPS enforced; `session.cookie_secure` on.
- [ ] Passwords hashed (already `password_hash`), CSRF tokens on money actions.
- [ ] Secrets out of the repo (use `DB_*` env vars; `.env` is git-ignored).
- [ ] Database backups configured.
- [ ] `install.php` removed or access-controlled after setup.
- [ ] Legal/compliance review for any real-money financial product in your jurisdiction.

