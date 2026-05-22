# Final Review Checklist

## Purpose
This checklist is used to verify whether the AI_RULES package in `docs/01_standards` is still intact, readable, and consistent enough for other GPTs to use.

## Structural Checklist
This checklist is a verification target. Do not claim every file exists unless it has been proven with `find` output or an equivalent command.

- `docs/01_standards/0001_index.md`
- `docs/01_standards/0002_decision_policy.md`
- `docs/01_standards/0003_gpt_bootstrap_prompt.md`
- `docs/01_standards/0004_session_start_protocol.md`
- `docs/01_standards/0005_handoff_template.md`
- `docs/01_standards/core/`
- `docs/01_standards/workflow/`
- `docs/01_standards/output/`
- `docs/01_standards/architecture/`
- `docs/01_standards/domain/`
- `docs/01_standards/stack/`
- `docs/01_standards/0099_changelog.md`

## Minimum Content Checklist
- `docs/01_standards/0001_index.md` contains the mandatory read order
- `docs/01_standards/0002_decision_policy.md` contains the rule hierarchy and GAP rule
- `docs/01_standards/core/0011_blueprint-first.md` contains the implementation gate
- `docs/01_standards/core/0012_step-by-step-execution.md` contains the one-active-step rule
- `docs/01_standards/core/0013_proof-and-progress.md` contains the rule that progress only increases with proof
- `docs/01_standards/domain/0050_final-domain-map.md` contains the final domain map
- `docs/01_standards/domain/0052_payment-lifecycle.md` contains the refund-vs-cancel rule
- `docs/01_standards/output/31-markdown-output-rule.md` contains the markdown contract
- `docs/01_standards/0003_gpt_bootstrap_prompt.md` contains the start-of-session checklist
- `docs/01_standards/0005_handoff_template.md` contains the proof and next-step sections

## Operational Checklist
- Another GPT can read `docs/01_standards/0001_index.md` and know the read order
- Another GPT can use `docs/01_standards/0003_gpt_bootstrap_prompt.md` as the bootstrap
- Another GPT can open a session with `docs/01_standards/0004_session_start_protocol.md`
- Another GPT can close a slice with `docs/01_standards/0005_handoff_template.md`

## Folder Content Checklist
- Every Markdown file has exactly one H1 heading.
- Active canonical standards files have a clear purpose and rules.
- Historical files have a clearly stated historical status or notice.
- Specialized DoD files or legacy references have a clear status.
- Specialized DoD files may not be read as proof that implementation is finished.
- There are no active stale paths to a legacy standards root or a legacy usage-guide location.
- A file may be renamed or moved only after a backlink audit and an owner decision.

## Closure Rule
If the checklist above is satisfied based on local proof, the AI_RULES package in `docs/01_standards` may be considered operationally ready.
