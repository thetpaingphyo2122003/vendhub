# Vending Machine

PHP 8.3 application for a vending machine: product stock, purchases, session login, and a token-protected API.

## Requirements

- PHP 8.2 or newer (developed on 8.3)
- Composer
- MySQL or MariaDB (XAMPP is enough)
- The web root must be the `public` directory

## Setup

```bash
composer install
copy .env.example .env
php bin/install.php
php -S localhost:8080 -t public public/router.php
```

Open http://localhost:8080

XAMPP alternative: start Apache and MySQL, point a virtual host's document root at `public`, and set `APP_URL` in `.env` to that host.

`.env` matches a default XAMPP MySQL install: host `127.0.0.1`, user `root`, empty password, database `vending_machine`.

## Administrator setup

Set `ADMIN_USERNAME` and a unique `ADMIN_PASSWORD` in `.env` before running `php bin/install.php` on a fresh database. The password must be at least 16 characters and contain uppercase, lowercase, number, and symbol characters. It is hashed with `password_hash()` and never committed to the repository.

To rotate an existing administrator password, set `RESET_ADMIN_PASSWORD=true` for one deployment, then return it to `false` after the reset completes.

Seeded products include the brief set (Coke 3.990, Pepsi 6.885, Water 0.500) plus snack and drink extras that match the PNGs in `public/assets/products`. Product tiles load `/assets/products/{slug}.png` when that file exists. The shop hero uses `public/assets/vending_machine.png`.

## Database design

Three tables, InnoDB, `utf8mb4`.

`users` holds people who can sign in. `password_hash` is produced by `password_hash()`. `role` is `admin` or `user`. Username is unique (3-32 letters, numbers, hyphens, or underscores).

`products` is the machine inventory. `price` is `DECIMAL(10,3)` so a price such as 6.885 is stored exactly. `quantity_available` is the stock on hand. `slug` is the public URL key (`/products/pepsi`), and it stays the same after a rename.

`transactions` is the purchase ledger. A user has many transactions. A product has many transactions. `unit_price` and `total` are copied onto the row at purchase time, so a later price change does not rewrite history. Stock decrement and the ledger insert run in one database transaction. A product or user with purchases cannot be deleted, because the foreign keys are `RESTRICT`.

The repository still implements update and delete for transactions. The screen only lists them. A sale is not edited after it is recorded.

## Where each task lives

1. Database description: this section and `database/schema.sql`
2. PDO connection and CRUD: `src/Core/Database/Connection.php` and `src/Repository/Pdo*Repository.php`
3. Sessions, password hashing, roles: `src/Service/AuthService.php`
4. Role checks: `src/Middleware/RequireRole.php` and the controller guards
5. Product CRUD: `src/Controller/ProductsController.php`
6. Purchase: `ProductsController::purchase()` and `src/Service/PurchaseService.php`
7. Admin CRUD screens: `/admin/products`, `views/products/manage.php`, `form.php`
8. List, pagination, sorting: `views/products/index.php`, `App\Model\ProductQuery`
9. Purchase screen: `views/products/purchase.php`
10. Routes: `#[Route]` attributes, read by `src/Core/Http/Router.php`
11. Purchase attribute route: `POST /products/{slug}/purchase`

Admin routes are under `/admin/*`. Shop routes stay under `/products/*`.
12. Product rules: `src/Validation/ProductValidator.php`
13. Browser checks: `public/assets/app.js`. Server errors render in the same form.
14. PHPUnit: `tests/Controller/ProductsControllerTest.php`
15. Constructor injection and mocks: controller tests never open MySQL
16. JSON API: `src/Controller/Api/ProductsApiController.php`
17. JWT: `src/Service/JwtService.php`, `src/Middleware/RequireJwt.php`, `POST /api/auth/token`

## API

```bash
curl -s http://localhost:8080/api/products

curl -s -X POST http://localhost:8080/api/auth/token ^
  -H "Content-Type: application/json" ^
  -d "{\"username\":\"user\",\"password\":\"User12345\"}"

curl -s -X POST http://localhost:8080/api/products/coke/purchase ^
  -H "Authorization: Bearer TOKEN" ^
  -H "Content-Type: application/json" ^
  -d "{\"quantity\":1}"
```

List and product detail are public. Creating, updating, and deleting products require an admin token. Purchase requires any valid token. A missing or bad token returns 401.

Sort values accepted by the list are `name`, `price`, and `quantity_available`. Anything else is treated as `name`.

## Tests

Dedicated suite lives in `tests/` (see `tests/README.md`).

```bash
composer test
composer test:regression    # full suite alias
composer test:integration
composer test:unit
composer test:controller
```

Suites:

- `tests/Unit` — validation, services, middleware, security (positive + negative)
- `tests/Controller` — Products + Auth + API with DI/mocks (PDF tasks 14–15)
- `tests/Integration` — multi-layer flows (router, register, purchase, API)
- Regression = full run via `composer test`

Controller tests mock repositories and never open MySQL. Integration tests wire multiple layers together.

## Deployment / production security

Any host that runs PHP 8.2+ and MySQL can serve this. Set the document root to `public`, copy `.env.example` to `.env`, fill in the database, run `composer install --no-dev`, then `php bin/install.php`.

Production `.env` must use:

```env
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
APP_URL=https://your-domain.example
JWT_SECRET=<at-least-32-random-characters>
JWT_TTL=3600
CORS_ALLOWED_ORIGINS=
TRUSTED_PROXIES=
```

The app refuses to boot in production with debug on or a weak/short `JWT_SECRET`. Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` on HTTPS. Login, admin login, register, and API token endpoints are rate-limited. CORS is same-origin only unless you explicitly list origins. Security headers (CSP, frame deny, nosniff, HSTS on HTTPS) are sent on every response.

The preview URL is the host you deploy to. This repository does not include a live server.

## Render deployment

The repository includes `Dockerfile` and `render.yaml` for a Render free web service. Render does not provide a free MySQL database with this app configuration. TiDB Cloud Starter is a MySQL-compatible free option; it uses port `4000` and requires TLS.

1. In Render, select **New > Blueprint** and connect this GitHub repository.
2. Enter the prompted `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` values. For TiDB Cloud, use its generated host and prefixed username, set `DB_NAME` to `vending_machine`, and keep `DB_PORT=4000`, `DB_SSL=true`.
3. Render generates `JWT_SECRET` automatically. `RUN_DATABASE_INSTALL=true` initializes the configured database and seeds the demo accounts on its first application start.
4. Deploy the Blueprint. Render exposes the service on its generated `onrender.com` URL.

For the free plan, allow for cold starts. Set `FORCE_HTTPS=true` only after configuring trusted proxy handling for your chosen hosting setup.
