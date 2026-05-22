# P1 - Handoff Policy

## Purpose
Create a closeout for a work slice that another GPT or the next session can use to continue execution.

## Mandatory Handoff Content
A handoff must include at minimum:
- metadata
- the work-page target or slice target
- the references used
- locked facts
- scope in
- scope out
- locked decisions
- files created or changed
- verification proof
- remaining gaps / risks
- next step

## Mandatory Rule
- A handoff may contain only proven facts.
- Do not write assumptions as handoff facts.
- Do not write that work is "done" if proof is still missing.
- The handoff must be clear enough for another GPT to continue without reinterpreting everything from scratch.

## Capacity Handoff Rule

If any session capacity indicator is below 80%, GPT must stop large implementation work and prepare a handoff.

The handoff must include the latest capacity footer:

~~~text
Session capacity:
- Reasoning capacity: xx%
- Status: switch to a new page
~~~

When this condition is reached, the next response should prioritize a continuation-ready handoff over another implementation patch.
