# P0 - Error Handling and Redaction

## Purpose
Ensure error handling is safe, consistent, and does not leak sensitive details.

## Mandatory Rule
- No raw error leak is allowed in user-facing output.
- Errors must follow the active envelope / handler if that contract is already locked.
- Sensitive details must be summarized or redacted.
- Logging and user-facing responses must be treated differently when needed for security.

## Security Principle
- Information that helps internal debugging is not automatically safe for user-facing responses.
- Error responses must be useful enough for the caller without exposing sensitive details.

## Forbidden Behavior
- Do not expose raw stack traces to user-facing output.
- Do not expose internal queries, secrets, tokens, credentials, or sensitive environment details.
- Do not bypass a locked error handler just because it is faster.
