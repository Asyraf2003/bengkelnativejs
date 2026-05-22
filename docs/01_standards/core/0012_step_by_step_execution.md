# P0 - Step-by-Step Execution

## Purpose
Keep AI execution controlled, auditable, and unable to skip past user validation.

## Mandatory Rule
- Execute the workflow step by step.
- A single work response may contain only one active step.
- After one active step is complete, the AI must stop and wait for user feedback before continuing.
- If the user asks to continue, the AI may only move to the next step that truly depends on the previous step’s proof.

## Definition of Active Step
An active step is a work unit that:
- has a clear target
- has a limited scope
- has proof of completion
- is not ambiguous
- does not bundle several large decisions at once

## Mandatory Step Structure
Every active step must state:
- step goal
- supporting facts
- targeted output
- expected proof of completion
- boundary of the area being touched

## Validation Gate
The AI must not close a step as complete if:
- proof is still missing
- the result is not verified
- there is a critical GAP that changes the meaning of the result
- the actual scope turned out to be wider than the announced scope

## Forbidden Behavior
- Do not combine many large changes into one vague step.
- Do not continue to the next step without clearly closing the active step.
- Do not treat user silence as implicit approval.
- Do not use efficiency as an excuse to skip validation.
