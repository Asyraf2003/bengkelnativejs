# P1 - Proof and Progress

## Purpose
Ensure progress is always tied directly to real proof, not belief or proposals.

## Mandatory Rule
- Progress cannot increase without proof.
- Every completion claim must point to real evidence.
- After one workflow step is finished, show progress as a percentage.

## Accepted Proof
Valid proof can be:
- command output
- file contents
- verified diff
- test results
- manual verification results
- explicit ADR / handoff / snapshot

## Mandatory Proof Structure
Every proof must at minimum explain:
- the command or artifact
- the visible result
- what the result means for the active step

## Progress Rule
- Progress represents workflow status, not just the amount of text or ideas.
- A proposal without execution does not increase progress.
- Newly created file structure may increase progress only if the step target is the creation of that structure.
- Rule revisions only increase progress if the file actually changed and was verified.

## Forbidden Behavior
- Do not claim green without output.
- Do not claim completion if you have only written a plan.
- Do not manipulate progress to look further along.
