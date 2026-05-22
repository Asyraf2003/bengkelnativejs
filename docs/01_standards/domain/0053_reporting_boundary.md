# P1 - Reporting Boundary

## Purpose
Ensure the reporting module continues to read the final domain without taking over core logic.

## Mandatory Rule
- Reporting only reads the final domain.
- Reporting must not become a source of truth.
- Do not place domain correction logic in the reporting layer.
- Do not build reports with terms that damage the final domain contract.

## Forbidden Behavior
- Do not use reporting queries as a place to repair domain state.
- Do not place the main lifecycle rules in the reporting module.
