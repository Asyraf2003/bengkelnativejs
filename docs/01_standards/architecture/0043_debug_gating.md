# P1 - Debug Gating

## Purpose
Prevent debug features from being active without explicit control.

## Mandatory Rule
- Debug routes, debug responses, and debug features must be gated explicitly.
- Do not assume the debug environment is active without valid configuration proof.
- Debug features must not leak into the general flow without a guard.

## Forbidden Behavior
- Do not expose debug endpoints by default.
- Do not place debug shortcuts on production paths without a gate.
