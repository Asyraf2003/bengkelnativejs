# P0 - Payment Lifecycle

## Purpose
Lock the payment lifecycle according to the domain decisions that have already been made.

## Mandatory Rule
- The end goal of the payment lifecycle is explicit partial payment.
- `paid` cannot be cancelled; if reversal is needed, the path is refund.
- Delete is allowed only for `draft` and must not create conflicting domain consequences.

## Implications
- Do not create a flow that allows cancel on `paid`.
- Do not create reversal shortcuts that bypass refund.
- Do not extend delete rights to statuses that already have final domain consequences.

## Forbidden Behavior
- Do not blur the difference between cancel and refund.
- Do not use UI terms that make the final lifecycle look different from the domain contract.
