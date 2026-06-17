# AI Usage Disclosure

## How AI was used

This submission was completed with **Cursor (Claude)** as a pair-programming assistant. The candidate started the schema, models, and basic calculator services manually; AI was used to complete the remaining core logic, tests, Filament screen, and documentation.

## Main workflows

1. **Codebase audit** — AI read existing migrations, models, and partial services to understand what was already built.
2. **Architecture completion** — AI designed and implemented payout orchestration, mock provider, accrual pipeline, and idempotency constraints on top of the candidate's ledger-first schema.
3. **Test-driven verification** — AI wrote Pest tests for the highest-risk flows (double payout, job retry, provider timeout) and iterated until green.
4. **Documentation** — AI drafted `README.md`, `ARCHITECTURE.md`, and this file; the candidate should review and personalize before submission.

## Fully generated vs manually designed

| Area | Origin |
|------|--------|
| Initial migrations & table design | Candidate |
| `RevenueCalculatorService` (platform cut + split) | Candidate |
| `LedgerService` / `BalanceService` concept | Candidate |
| Enum namespace fixes, provider layer, jobs, commands | AI-generated, candidate-reviewed |
| Idempotency constraints migration | AI-proposed from candidate's schema |
| Pest test suite | AI-generated |
| Filament read-only instructor screen | AI-generated via Filament artisan stubs |
| Architecture documentation | AI-drafted |

## Engineering decisions made by the candidate (with AI execution)

These are the decisions that should be explained in the technical interview:

1. **Ledger as source of truth** — balances are sums of immutable entries, not mutable balance columns.
2. **Daily linear accrual** — revenue earns over the subscription term, enabling sensible refunds.
3. **Floor rounding with remainder to first instructor** — deterministic, zero-loss integer math.
4. **Three-layer payout idempotency** — batch key, payout idempotency key, provider idempotency key.
5. **Timeout → pending_confirmation → status check** — never write payout ledger entry until success is confirmed.
6. **Morph references on ledger entries** — tie earnings/payouts back to schedules and payout rows for audit.

## What differentiates this solution

- Treats money as an **audit trail problem**, not a CRUD balance update problem
- Separates **allocation** (schedules), **recognition** (accrual), and **settlement** (payouts)
- Tests focus on failure modes the quest explicitly cares about, not happy-path CRUD
- Documents trade-offs instead of hiding ambiguity

## Trade-offs intentionally chosen

| Choice | Trade-off |
|--------|-----------|
| Daily schedules vs single earning | More rows, but better refund semantics and write spreading |
| Derived balances vs cached balances | Simpler correctness; reads can be slower at very large scale |
| Snapshot payout amount | Prevents over-payment if balance changes mid-batch; may need another batch |
| Mock provider in DB | Easy to test; not a real async webhook integration |

## Before submitting

The candidate should:

1. Re-read all code and be able to explain any file live
2. Record the required video walkthrough in their own words
3. Personalize this document with their actual AI workflow
4. Run `php artisan test` and capture a screenshot of passing tests
