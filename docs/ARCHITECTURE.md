# Architecture

## Problem

Students pay upfront for platform subscriptions. Multiple instructors contribute content within a single subscription. The platform must:

1. Track what each instructor is **owed**, **paid**, and **outstanding**
2. Pay instructors on a schedule through an unreliable provider
3. Stay correct under retries, duplicate runs, timeouts, and refunds
4. Scale to hundreds of thousands of subscriptions and tens of millions of accrual rows

## Domain model

```
Student ──< Subscription >──< Instructor (allocation %)
                │
                ├──< RevenueSchedule (daily accrual, processed flag)
                └──< LedgerEntry (immutable money movements)

PayoutBatch ──< Payout ──> Instructor
                    │
                    └── morph ──> LedgerEntry (payout debit)

ProviderTransfer (mock provider audit log)
```

### Tables

| Table | Role |
|-------|------|
| `subscriptions` | Paid plan, term dates, platform fee % |
| `subscription_instructors` | How net revenue is weighted per instructor |
| `revenue_schedules` | Future daily earnings per instructor per subscription |
| `ledger_entries` | Source of truth for balances (signed integer cents) |
| `payout_batches` | Groups a payout run (`batch_key` is unique) |
| `payouts` | One instructor payment attempt (`idempotency_key` is unique) |
| `provider_transfers` | Mock provider state for status reconciliation |

## Revenue allocation strategy

### Step 1 — Split payment

```
net = amount_paid - floor(amount_paid * platform_fee_percentage / 100)
```

Each instructor receives:

```
share_i = floor(net * allocation_percentage_i / 100)
remainder → first instructor
```

This guarantees `sum(shares) === net` with no fractional cents.

### Step 2 — Accrue over time

Instead of recognizing all instructor revenue on day one, we **linearly accrue daily** across the subscription term:

- Monthly → 30 daily schedules
- Quarterly → 90
- Annual → 365

Each schedule row stores `earned_at`, `amount`, and `processed`.

**Why daily accrual?**

- Matches “money counts as earned over the service period”
- Refunds only affect unearned future schedules
- Spreads write load instead of one huge ledger spike per payment
- Scales with partitioned queries on `(instructor_id, earned_at)`

### Step 3 — Accrual job

`revenue:accrue` selects `processed = false AND earned_at <= today`, locks each row, creates a ledger `earning` entry (idempotent via unique `reference` morph), then marks `processed = true`.

## Balance calculation

Balances are **derived from the ledger**, not stored as mutable counters:

| Metric | Query |
|--------|-------|
| Outstanding | `SUM(ledger_entries.amount)` |
| Total earned | `SUM(amount) WHERE type = earning` |
| Total paid | `ABS(SUM(amount) WHERE type = payout)` |

This avoids drift between balances and audit trail.

## Payout architecture

```
payouts:process
    └── PayoutService::createBatch(batch_key)
            ├── firstOrCreate PayoutBatch
            └── for each instructor with outstanding > 0
                    firstOrCreate Payout(idempotency_key)
    └── dispatch ProcessPayoutJob per pending payout
```

### Idempotency layers

| Layer | Mechanism |
|-------|-----------|
| Batch | Unique `batch_key` |
| Payout row | Unique `idempotency_key = {batch_key}:instructor:{id}` |
| Provider | Unique `idempotency_key` in `provider_transfers` |
| Ledger | Unique `(reference_type, reference_id)` |
| Job retry | `processPayout()` exits early if status is `paid` |

Running the payout command twice with the same `--batch-key` reuses the same batch and payout rows. Processing an already-paid payout is a no-op.

## Provider timeout handling

`MockPaymentProvider` simulates three outcomes:

1. **Success** — immediate confirmation
2. **Failed** — no money moved
3. **Timeout after success** — money moved, but HTTP times out

On timeout:

- Payout → `pending_confirmation`
- `provider_reference` stored
- `ConfirmPayoutStatusJob` (or a retry of `processPayout`) calls `checkStatus()`
- Only after provider confirms success do we write the payout ledger entry

The provider also returns the same result for duplicate `idempotency_key` calls.

## Refund handling

When a student leaves mid-term:

1. Subscription status → `refunded`, `ends_at` updated
2. Delete **unprocessed** schedules with `earned_at > refund_date`
3. Keep already-accrued ledger earnings (service was consumed)

If a payout already happened, the instructor keeps paid amounts; future accruals are simply cancelled.

## Scaling considerations

| Concern | Approach |
|---------|----------|
| Millions of schedule rows | Chunked accrual (`chunkById(500)`), index on `(instructor_id, earned_at)` |
| Payout concurrency | Row-level locks on payouts, unique constraints as backstop |
| Ledger growth | Append-only, partitionable by `created_at` / `instructor_id` |
| Balance reads | Materialized balance cache could be added later; ledger remains source of truth |

## Known limitations

- No mid-term plan upgrade/downgrade (senior bonus discussion only)
- No multi-currency support
- Mock provider only (no real PSP integration)
- Payout amount is snapshot at batch creation; concurrent earnings during processing are picked up in the next batch
- Failed payouts are not automatically retried with a new batch (manual re-run required)

## Senior bonus — plan changes mid-term

If a student upgrades from monthly to annual mid-term:

1. **Close** the old subscription accrual schedule on the change date
2. **Credit** unused prepaid platform value as a subscription adjustment (not instructor clawback unless refunding)
3. **Open** a new subscription with a new schedule for the upgraded plan
4. **Prorate** using remaining days and price difference, stored as explicit adjustment ledger entries

Instructor allocations would be frozen per subscription version to keep auditability.
