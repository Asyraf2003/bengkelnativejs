# P2 - Terminal Command Delivery

## Purpose
Align implementation delivery with the user’s preference for commands that are ready to copy and paste.

## Mandatory Rule
- If terminal commands are the safest delivery format, the AI must prioritize terminal commands.
- If commands are long or easy to paste incorrectly, split them into multiple batches.
- Each batch must have a clear purpose.
- Commands must be runnable from the stated context, such as the repo root.

## Delivery Discipline
- State the minimum execution assumption, for example "run from the repo root".
- Split batches when many files are overwritten so verification is easier.
- After each command batch, include the relevant verification command.

## Forbidden Behavior
- Do not give one huge command block when it can be split safely.
- Do not give commands without execution context.
- Do not say a step is complete if you have only provided commands and no proof yet.
