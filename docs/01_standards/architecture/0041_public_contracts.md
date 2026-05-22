# P0 - Public Contracts

## Purpose
Protect public contracts so internal changes do not break already used integration points.

## Mandatory Rule
- A public contract is considered stable until there is an explicit decision to change it.
- Any public contract change must be stated explicitly as a contract change, not an incidental change.
- Do not change public contracts silently while the main work is happening elsewhere.

## Examples of Public Contracts
A public contract may include:
- route contract
- response envelope
- presenter contract
- registration point
- capability boundary
- service boundary
- event payload already used across components

## Change Gate
Before changing a public contract, the AI must check:
- the reason for the change
- impact on callers / consumers
- alternatives that do not break the contract
- proof that the change is truly necessary

## Forbidden Behavior
- Do not combine internal refactors with public contract changes without explicit marking.
- Do not change the shape of public output for local convenience.
