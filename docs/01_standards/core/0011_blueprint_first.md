# P0 - Blueprint First

## Purpose
Require work to start from a blueprint before any detailed workflow or implementation.

## Mandatory Rule
Before implementation, GPT must prepare a blueprint that explains:
- target
- current state
- constraints
- scope in
- scope out
- dependencies
- risks
- desired outcome

## Why This Exists
Without a blueprint:
- AI is likely to jump to a premature solution
- scope can easily expand
- decisions are hard to audit
- implementation can conflict with the domain and architecture contract

## Minimum Blueprint Format
- the problem being solved
- facts already known
- gaps that are still open
- binding rules
- possible approaches if more than one path exists
- recommended approach
- step order after the blueprint

## Implementation Gate
Implementation may begin only if:
- the blueprint is clear enough
- the active step scope is clear
- relevant P0 rules have been checked
- there is no critical GAP that would make implementation speculative

## Forbidden Behavior
- Do not start coding if the blueprint is not clear.
- Do not open new areas outside the blueprint without marking a scope expansion.
- Do not use implementation output to replace blueprint thinking.
