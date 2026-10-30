# Store Order & Inventory Mini-System

A small Laravel application for a retail counter. It provides a browser-based order desk and JSON API for products, stock, customer order history, and order creation.

## Requirements

- PHP 8.3 or later
- Composer
- Node.js and npm
- MySQL 8 or later (with the PHP `pdo_mysql` extension enabled)

## Setup

From the project directory:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE inventory_mini_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
npm install --ignore-scripts
npm run build
```

If `.env` already exists, keep it and check that `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` match your MySQL server. The example uses a local MySQL server, database `inventory_mini_system`, and user `root`; update the credentials as needed. Set `LOW_STOCK_THRESHOLD` as desired. Configure a persistent queue connection before running the worker in a shared or production environment.

Start the application and queued job worker in separate terminals:

```powershell
php artisan serve
php artisan queue:work
```

Open the URL printed by `php artisan serve`. The sample catalog and customers are loaded by `php artisan migrate --seed`.

## API

All API routes use the `/api` prefix and return JSON.

### Create an order

`POST /api/orders`

```json
{
  "customer": {
    "name": "Alex Buyer",
    "email": "alex@example.com"
  },
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

On success, returns `201 Created` with the customer, order totals, and item price/tax snapshots. Invalid input or insufficient stock returns `422 Unprocessable Content` with validation errors. The service locks products in ID order, checks all requested quantities, and uses conditional stock-decrement updates within one database transaction; a failed line rolls back the complete order. Use a database with row-level locking (such as MySQL or PostgreSQL) for concurrent production traffic.

### Browse products

- `GET /api/products`
- `GET /api/products/low-stock`
- `GET /api/products/low-stock?threshold=10`

The low-stock endpoint returns products with `stock_on_hand` strictly less than the given threshold. The query parameter overrides `LOW_STOCK_THRESHOLD` (default `5`).

### Customer order history

`GET /api/customers/orders?email=alex%40example.com`

Returns the customer's orders, newest first. An unknown email returns `404`.

## Design decisions and assumptions

- A customer is created on the first order for a normalized (trimmed, lower-case) email. Later orders reuse that customer and do not silently overwrite their saved name.
- Product code and customer email are unique. Each order has one customer and one or more order-item rows.
- Product price and tax percentage are copied to each order item when the order is placed, preserving the original values even if the catalog changes later.
- Monetary values use two decimal places. Tax is calculated and rounded per line to the nearest cent, then summed for the order. The demo UI displays currency-neutral decimal amounts because no currency was specified.
- Low stock means strictly below the threshold (`<`, not `<=`). A threshold of zero therefore returns no products.
- The confirmation job logs a simulated email and is queued after order persistence. No real mail service is configured.
- The sample UI is a lightweight counter workflow, not an authenticated or multi-user application; authentication and product-management screens were outside the brief.
- MySQL is the default application database and provides row-level locks for concurrent order processing. PHPUnit feature tests use an isolated in-memory SQLite database.
- Automated AI-assisted implementation was used through the coding assistant in VS Code. This environment cannot capture authentic screenshots from the IDE chat panel or record a narrated walkthrough; add the requested prompt screenshots under `prompts/` and attach a narrated 5–10 minute screen recording before submission. No screenshots or video have been fabricated.

## Tests

Run the PHPUnit suite with:

```powershell
php artisan test
```

The feature tests cover successful order creation and tax calculation, stock deduction, insufficient-stock rejection, rollback across a multi-line order, customer history, low-stock filtering, and request validation.

## Submission checklist

- Push this project to a private GitHub repository and share it with the requested reviewers.
- Add authentic screenshots of the IDE prompts under `prompts/`.
- Record and attach the narrated application walkthrough.
