# P1 - Audit and DoD

## Purpose
Make auditability and Definition of Done a mandatory part of delivery.

## Mandatory Rule
- Important changes must be auditable.
- A "done" claim must be backed by verification relevant to the step scope.
- The DoD depends on the change context, but it cannot be empty.

## Typical DoD Components
Depending on context, a DoD may include:
- format / lint
- test
- audit
- sanity check
- file / output inspection

## Proof Rule
If you mention verification:
- include the command or artifact
- include the result
- include what the result means for the active step

## Forbidden Behavior
- Do not write a DoD as if it were complete when it is only a plan.
- Do not write abstract verification without concrete evidence.
