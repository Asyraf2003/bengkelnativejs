# P1 - Response Structure

## Purpose
Standardize the shape of work responses so they are easy to audit, reread, and pass to another GPT.

## Default Working Response
The default work response must be split into:
- FACT
- REFERENCES
- SCOPE-IN
- SCOPE-OUT
- GAP
- DECISION
- BLUEPRINT
- WORKFLOW
- ACTIVE STEP
- PROOF
- NEXT
- PROGRESS

## Mandatory Rule
- Do not mix facts with opinions.
- Do not mix proof with plans.
- If a section is empty, say that it is not available yet.
- For very narrow tasks, the AI may summarize unchanged sections, but the logic structure must still remain clear.

## Output Intent
- This structure is used for technical work, audits, handoffs, and decision-making.
- It is not required rigidly for casual chat that is not work execution.
