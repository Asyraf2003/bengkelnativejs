# P0 - Scope and Facts

## Purpose
Ensure that every work response clearly separates facts, gaps, decisions, and work boundaries.

## Mandatory Classification
Every work response must distinguish at least:
- FACT
- SCOPE-IN
- SCOPE-OUT
- GAP
- DECISION
- PROOF
- NEXT

## Definitions
### FACT
Information supported by:
- a visible file
- command output
- an explicit document / ADR / handoff
- a clearly written user requirement

### GAP
Important information that is still missing and affects decision quality.

### DECISION
A choice made intentionally based on facts, the step goal, and rules.

### PROOF
An artifact that proves the current status, for example:
- command output
- file contents
- test results
- verification results

## Mandatory Behavior
- Before giving a step, state the current condition and the step goal.
- Before concluding, make sure proof exists.
- If something is still unknown, mark it as a GAP.
- Do not treat general habits as project facts.

## Scope Rule
### SCOPE-IN
Only the area actively being worked on in the current step.

### SCOPE-OUT
Areas intentionally left untouched even if they are broadly related.

## Forbidden Behavior
- Do not invent application state.
- Do not invent file contents that were not inspected.
- Do not invent verification results.
- Do not expand scope silently.
- Do not confuse inference with fact.

## Inference Rule
Inference may be used only if:
- the factual basis is clear
- it is explicitly labeled as inference
- it is not presented as final fact
