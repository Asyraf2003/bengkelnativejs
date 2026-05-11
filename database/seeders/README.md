# Database Seeders Legacy Notice

## Status

All current PHP seeders under this directory are classified as legacy compatibility seeders unless a later source-inspection document explicitly marks a file as part of the clean seeder contract.

This directory is still active runtime code.

Legacy compatibility does not mean unused.

Legacy compatibility means:

- do not extend casually
- do not treat as final clean seeder architecture
- do not delete before replacement proof exists
- do not claim production-safe behavior without verification
- do not claim idempotency without rerun proof
- do not claim finance correctness from seed data alone

## Source Of Truth

Read these documents before changing seeders:

- docs/03_blueprints/seeder/2026-05-11-legacy-to-clean-seeder-blueprint.md
- docs/03_blueprints/seeder/2026-05-11-legacy-seeder-manifest.md
- docs/adr/0023-seeder-credential-and-environment-safety.md
- docs/blueprint/security/2026-05-06-seeder-credential-and-environment-safety-blueprint.md
- docs/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md
- docs/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md

## Current Direction

The clean seeder system must move toward:

1. explicit identity/access local-test seed
2. deterministic domain baseline seed
3. deterministic scenario seed
4. deterministic load seed
5. explicit production bootstrap path
6. audit command proof
7. finance invariant tests

## Runtime Safety Rule

Do not change runtime seeder behavior from this notice alone.

Any runtime seeder change must start from:

1. source inspection
2. narrow blueprint
3. characterization proof where feasible
4. targeted implementation
5. verification output
6. manifest/document update

## Credential Safety Rule

Predictable privileged credentials are allowed only in explicitly local/testing workflows.

Production-like environments must not receive predictable privileged credentials from seeders.

Unknown environments are production-like by default.

## Next Cleanup Priority

The first runtime cleanup priority is `UserSeeder` credential boundary, because current entrypoint inspection showed default, level 1, level 2, and level 3 seed paths reach user credential seeding.

Do not start broad file-level deprecation edits before that risk is guarded or explicitly deferred by owner decision.
