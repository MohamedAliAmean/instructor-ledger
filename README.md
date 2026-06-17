# Instructor Revenue Ledger

Core money engine for an LMS: subscription revenue is allocated to instructors, accrued over time, and paid out safely through an unreliable external provider.

Built for the **Career 180 — Instructor Revenue Ledger** hiring quest.

## Tech stack

- Laravel 13
- Filament 5 (admin read-only ledger view)
- Pest (tests)
- MySQL
- Queued payout jobs

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=InstructorLedger
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create databases:

```sql
CREATE DATABASE InstructorLedger;
CREATE DATABASE InstructorLedger_test;
```

Run migrations and seed demo data:

```bash
php artisan migrate
php artisan db:seed
```

Create an admin user for Filament (or use seeded user):

- Email: `admin@example.com`
- Password: `password`

Start the app:

```bash
php artisan serve
```

Visit Filament admin: `http://localhost:8000/admin`

## API & Swagger

REST API base URL: `http://localhost:8000/api`

Swagger UI (interactive API testing): **`http://localhost:8000/api/documentation`**

Regenerate OpenAPI docs after changing controllers:

```bash
php artisan l5-swagger:generate
```

### API endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/instructors` | List instructors with balances |
| GET | `/api/instructors/{id}` | Instructor balance details |
| GET | `/api/instructors/{id}/payouts` | Payout history |
| POST | `/api/subscriptions` | Create subscription + allocate revenue |
| GET | `/api/subscriptions/{id}` | Subscription details |
| POST | `/api/subscriptions/{id}/refund` | Process refund |
| POST | `/api/revenue/accrue` | Accrue due revenue to ledger |
| GET | `/api/payouts` | List payouts |
| GET | `/api/payouts/{id}` | Payout details |
| POST | `/api/payouts/process` | Run payout batch (`sync: true` for immediate testing) |

### Quick Swagger test flow

1. Open `http://localhost:8000/api/documentation`
2. `POST /subscriptions` — create a subscription (use existing instructor IDs from Filament or seeder)
3. `POST /revenue/accrue` — body: `{ "as_of": "2026-01-31" }`
4. `GET /instructors` — verify outstanding balance
5. `POST /payouts/process` — body: `{ "batch_key": "swagger-test", "sync": true }`
6. `GET /instructors/{id}/payouts` — verify payout history

## Running tests

```bash
php artisan test
```

Tests use the `InstructorLedger_test` database (see `phpunit.xml`). DB credentials are read from your `.env`.

## Key commands

| Command | Purpose |
|---------|---------|
| `php artisan revenue:accrue` | Move due revenue schedules into instructor ledger entries |
| `php artisan revenue:accrue --date=2026-01-15` | Accrue up to a specific date |
| `php artisan payouts:process` | Create payout batch and dispatch payout jobs |
| `php artisan payouts:process --batch-key=payout-2026-06-15` | Idempotent payout run with a stable batch key |

Queue worker (required for async payouts):

```bash
php artisan queue:work
```

## Assumptions

1. **Money is stored in integer cents** (piastres) to avoid floating-point errors.
2. **Platform fee** is deducted first using `floor()`; instructors split the net amount.
3. **Instructor split** uses percentage weights with `floor()` per instructor; remainder cents go to the first instructor.
4. **Revenue is earned daily** over the subscription term (linear accrual), not all on day one.
5. **Refunds** cancel future unprocessed schedules; instructors keep amounts already accrued.
6. **One subscription** can include multiple instructors via `subscription_instructors.allocation_percentage`.
7. **Payout idempotency** uses unique `idempotency_key` per instructor per batch and unique ledger references per payout/schedule.

## Documentation

- [Architecture & design decisions](docs/ARCHITECTURE.md)
- [AI usage disclosure](docs/AI_USAGE.md)

## Project structure

```
app/
├── Console/Commands/          # revenue:accrue, payouts:process
├── Contracts/                 # PaymentProviderInterface
├── DTOs/                      # TransferResult
├── Enums/                     # Domain statuses and types
├── Exceptions/                # PaymentTimeoutException
├── Filament/Resources/        # Read-only instructor balance UI
├── Jobs/                      # ProcessPayoutJob, ConfirmPayoutStatusJob
├── Models/                    # Eloquent models
├── Services/
│   ├── Payment/               # MockPaymentProvider
│   ├── BalanceService.php     # owed / earned / paid queries
│   ├── LedgerService.php      # append-only ledger writes
│   ├── PayoutService.php      # batch + payout orchestration
│   ├── RefundService.php      # mid-term refund handling
│   ├── RevenueAccrualService.php
│   ├── RevenueAllocationService.php
│   └── RevenueCalculatorService.php
database/migrations/           # schema
tests/Unit/                    # Pest tests
docs/                          # architecture + AI notes
```

## Video submission

Record a 15–20 minute walkthrough covering architecture, failure scenarios, tests, and engineering decisions. Demonstrate at least:

- Running payouts twice
- Retried jobs
- Provider timeout + status confirmation
- Refund after allocation
- Rounding edge cases
