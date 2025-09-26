# XChaChange

A Laravel + Inertia/Vue application for managing currency exchange orders.

## Features
- **User-authenticated order creation** with source and foreign amounts
- **Active currency management** with configurable exchange rates and surcharges
- **Real-time preview** of calculated amounts (surcharge, totals)
- **Email notifications** on order placement (optional per currency)
- **Comprehensive test suite** with unit and feature tests

## Tech Stack
- **Backend:** Laravel (PHP)
- **Frontend:** Inertia.js + Vue 3, Vite, Tailwind CSS
- **Database:** MySQL/PostgreSQL/SQLite (via Laravel)
- **Testing:** PHPUnit with comprehensive test coverage

## Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- A database (MySQL/PostgreSQL/SQLite)
- A currency API key (e.g., CurrencyLayer)

## Getting Started

### 1) Clone and install dependencies
```bash
# Clone
git clone <repo-url>
cd XChaChange

# Install PHP dependencies
composer install

# Install JS dependencies
npm install
```

### 2) Environment configuration
Copy the example env and update values as needed.
```bash
cp .env.example .env
```

Recommended settings to review in `.env`:
- Database (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
- App URL (`APP_URL`)
- Order Notifications (`ORDER_NOTIFICATIONS_TO`)
- Currency API Key (`CURRENCY_LAYER_API_KEY`)

Recommended settings to review in `config/services.php`:
- `services.currency.base_url` (e.g., https://api.currencylayer.com)
- `services.currency.currencies` (e.g., USD, EUR, GBP, KES)
- `services.currency.source` (e.g., ZAR)
- `services.currency.format` (e.g., 1)

### 3) Generate app key
```bash
php artisan key:generate
```

### 4) Run migrations and seeders
```bash
php artisan migrate
php artisan db:seed
```

### 5) Build assets and run the dev server
Two terminals are recommended:
```bash
# Terminal 1: Vite dev server
npm run dev

# Terminal 2: Laravel server
php artisan serve
```
Visit the app at the URL printed by `php artisan serve` (e.g., http://127.0.0.1:8000).

## Project Structure (high level)
- `app/Http/Controllers/Api/` — API controllers (`CurrencyController`, `OrderController`)
- `app/Models/` — Eloquent models (`Currency`, `Order`, `User`)
- `resources/js/` — Inertia/Vue 3 app (Inertia pages, layouts, components)
- `resources/views/` — Blade templates (Inertia root)
- `routes/api.php` — API routes
- `routes/web.php` — Web routes
- `config/services.php` — Currency API settings
- `database/migrations/` — Schema definitions
- `database/seeders/` — Initial data
- `database/factories/` — Model factories for testing
- `tests/Unit/` — Unit tests for models and commands
- `tests/Feature/` — Feature tests for API endpoints

## Key Files
- Frontend order page: `resources/js/Pages/NewOrder.vue`
- Order API: `app/Http/Controllers/Api/OrderController.php`
- Currency API: `app/Http/Controllers/Api/CurrencyController.php`
- Order model: `app/Models/Order.php`
- Currency model: `app/Models/Currency.php`
- User model: `app/Models/User.php`

## API Reference
Defined in `routes/api.php`.

### Currencies
- `GET /api/currencies` — Index
- `GET /api/currencies/list` — Active currencies list
- `GET /api/currencies/inactive` — Inactive currencies list
- `GET /api/currencies/source` — The configured source currency code (e.g., ZAR)
- `GET /api/currencies/{currencyCode}` — Show one
- `POST /api/currencies/update/{currencyCode}/{field}/{value}` — Update a field
- `POST /api/currencies/updateAll` — Bulk update
- `POST /api/currencies/activate/{currencyCode}` — Activate
- `POST /api/currencies/deactivate/{currencyCode}` — Deactivate
- `POST /api/currencies/enableSendOrderEmail/{currencyCode}` — Enable order email
- `POST /api/currencies/disableSendOrderEmail/{currencyCode}` — Disable order email

### Orders
- `GET /api/orders` — List all orders
- `GET /api/orders/{id}` — Show an order
- `POST /api/orders` — Create an order
- `GET /api/orders/user/{userId}` — Show orders for a user
- `GET /api/orders/currency/{currencyId}` — Show orders for a currency

### Updating Currencies via Manual API Command
```bash
php artisan currencies:fetch
```

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test tests/Unit/CurrencyTest.php
php artisan test tests/Feature/OrderControllerTest.php

# Run with coverage (requires Xdebug)
php artisan test --coverage
```
### Notes
- Please find ERD in `docs/XChaChange_ERD.png` - Made with personal Canva Account and was my first step in planning for this project
- Please find API documentation in `docs/XChaChange_API_Documentation.pdf`
- Laravel Breeze was used for authentication
- Although I know this may be a detriment to me, AI was used for vue views as I don't have much experience with vue + for unit tests as I don't have much experience with unit testing either. I am however proud of what I have done and will continue to improve my skills and learn as I go.
- Logo was made with Canva via templates