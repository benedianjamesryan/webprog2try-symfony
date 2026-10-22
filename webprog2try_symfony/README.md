# FROSTCORE Symfony project

This is the Symfony port of the legacy PHP/XAMPP cooler shop. It uses Twig for
server-rendered pages, Doctrine ORM for persistence, and a MariaDB container for
local development.

## Start the database

From this directory, run:

```powershell
docker compose up -d
```

The database is exposed on `127.0.0.1:3306` and persists in the
`frostcore_mysql_data` Docker volume. The committed `.env` values are
development-only.
If you used the previous Compose volume, it is left untouched but will not be
used automatically; copy or back up existing database data before switching.
For custom local credentials, set `DB_PASSWORD`, `DB_ROOT_PASSWORD`, and a
matching `DATABASE_URL` in the ignored `.env.local`, then start Compose with
`docker compose --env-file .env.local up -d`. Do not use the
development defaults in production.

phpMyAdmin is available at <http://localhost:8080> after starting the Compose
services. The MySQL container is named `frostcore_mysql`, phpMyAdmin is named
`frostcore_phpmyadmin`, the shared network is `frostcore_net`, and the persistent
database volume is `frostcore_mysql_data`.

## Install and initialize Symfony

```powershell
composer install
php bin/console doctrine:migrations:migrate --no-interaction
```

The initial migration creates the six shop tables and inserts the eight catalog
products. The legacy SQL dump did not contain customer, admin, order, cart, or
review records, so those are created by the Symfony application.

Run the site with the Symfony CLI:

```powershell
symfony serve
```

The home page is public and signed-out visitors see only Why Cooling, Product,
Specs, and Reviews in its main navigation. About us and Contact require sign-in.
The shop catalog and customer pages also require sign-in: a guest who clicks
**SHOP NOW** is sent to `/login`, where **CREATE ACCOUNT** opens registration.
After registering, sign in to continue to the catalog.

To grant an existing account administrator access, run:

```powershell
php bin/console app:user:promote-admin name@example.com
```

Then sign in again at `/login`. Admin accounts can use the protected storefront
and the **ADMIN** link to reach `/admin`; the admin sidebar's **View storefront**
link returns to the public home page. New registrations are always customers;
never expose the promotion command to an untrusted user.

## Store flows

- Browse and filter products at `/products`, and open individual product pages
  for details and reviews.
- Signed-in customers can manage a stock-limited cart, check out with COD,
  GCash, or Card as the recorded payment method, and view their own order
  history.
- Administrators can manage products, update order statuses, review customers,
  manage brands at `/admin/brands`, and moderate product reviews under `/admin`.
- The contact form opens an email draft in the visitor's configured email
  application; it does not send or store messages.

The Brand entity uses the PHP property `brandName`; Doctrine maps it to the
`brand_name` database column. After starting the database, generate and apply
its schema migration with:

```powershell
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

To stop the database while preserving its data, run `docker compose down`.
