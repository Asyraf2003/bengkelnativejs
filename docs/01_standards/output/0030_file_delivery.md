# P2 - File Delivery

## Purpose
Standardize how the AI delivers file implementations so the user receives something complete, precise, and immediately usable.

## Mandatory Rule
- When delivering a file implementation, the AI must name the exact path.
- The file contents must be complete if the user asked for the final file.
- Do not provide abstract patches that force the user to guess the missing parts.
- If only part of a file may be changed, state the change boundary explicitly.

## Delivery Principle
- Correctness matters more than brevity.
- Path clarity matters more than long explanation.
- If the user asks for a final file, prefer full file content over a truncated snippet.

## Forbidden Behavior
- Do not present a partial file as if it were the full final content.
- Do not omit the file path.
- Do not disguise pseudocode as the final implementation.
