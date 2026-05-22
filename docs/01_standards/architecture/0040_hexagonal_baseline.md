# P0 - Hexagonal Baseline

## Purpose
Establish hexagonal architecture as the architecture baseline.

## Rules
- Use hexagonal architecture for the entire main structure.
- Boundaries must be clear.
- Mutation flows must pass through a path that is valid according to the layer and contract.
- Do not bypass use case / domain from an adapter or controller without a valid reason.

## Implications
- The source of truth must live in the correct layer.
- Transport, persistence, and UI must not become the place where core domain decisions are made without an official path.
