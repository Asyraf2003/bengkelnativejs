# AI_RULES Index

## Status
This document is the mandatory entry point for every GPT/AI assistant that works on this project.

AI_RULES is the name of the AI working rules package for this repo. The canonical standards package currently lives in `docs/01_standards`.

## Purpose
AI_RULES constrains AI behavior so that it:
- does not make assumptions
- does not leave the blueprint
- does not skip the active step
- does not invent facts, repo status, test results, or decisions
- stays aligned with the project domain and architecture contracts

## Mandatory Read Order
Every GPT must read this order before giving work guidance:

1. 0002_decision_policy.md
2. 0003_gpt_bootstrap_prompt.md
3. 0004_session_start_protocol.md
4. 0007_ai_usage_guide.md
5. 0008_ai_personalization_profile.md
6. core/0010_scope_and_facts.md
7. core/0011_blueprint_first.md
8. core/0012_step_by_step_execution.md
9. core/0013_proof_and_progress.md
10. workflow/0020_response_structure.md
11. workflow/0021_active_step_policy.md
12. workflow/0024_session_capacity_policy.md
13. architecture/0040_hexagonal_baseline.md
14. architecture/0041_public_contracts.md
15. architecture/0042_error_handling_and_redaction.md
16. architecture/0043_debug_gating.md
17. architecture/0044_audit_and_dod.md
18. domain/0050_final_domain_map.md
19. domain/0051_ui_terms_and_status.md
20. domain/0052_payment_lifecycle.md
21. domain/0053_reporting_boundary.md
22. stack/0060_laravel_rules.md
23. stack/0061_go_rules.md
24. stack/0062_aws_baseline.md
25. output/0030_file_delivery.md
26. output/0031_markdown_output_rule.md
27. output/0032_blade_rule.md
28. output/0033_terminal_command_delivery.md
29. 0005_handoff_template.md
30. 0006_final_review_checklist.md
31. 0099_changelog.md

## Constitution Summary
- Do not assume.
- Every instruction must be grounded in facts, current conditions, step goals, and evidence.
- Start from the blueprint.
- After the blueprint, build the workflow step by step.
- Only one active step is allowed per work response.
- After one active step is complete, wait for user feedback.
- Every technical work response must end with session capacity status.
- Progress may increase only when there is real proof.
- Do not reopen final domain decisions without a real conflict and strong evidence.

## Priority Model
- P0 = core rules, not to be broken without an explicit decision
- P1 = workflow enforcement and architecture alignment
- P2 = delivery format and output preference

## Operational Bootstrap for GPT
Before answering, GPT must verify:
1. what facts actually exist
2. the current step goal
3. scope in and scope out
4. which P0 rules apply
5. whether the data is sufficient to continue
6. if data is insufficient, stop at GAP
7. whether session capacity is still safe for large implementation

## Module Map
- 0002_decision_policy.md
- 0003_gpt_bootstrap_prompt.md
- 0004_session_start_protocol.md
- 0007_ai_usage_guide.md
- 0008_ai_personalization_profile.md
- 0005_handoff_template.md
- 0006_final_review_checklist.md
- core/
  - 0010_scope_and_facts.md
  - 0011_blueprint_first.md
  - 0012_step_by_step_execution.md
  - 0013_proof_and_progress.md
- workflow/
  - 0020_response_structure.md
  - 0021_active_step_policy.md
  - 0022_option_evaluation.md
  - 0023_handoff_policy.md
  - 0024_session_capacity_policy.md
- output/
  - 0030_file_delivery.md
  - 0031_markdown_output_rule.md
  - 0032_blade_rule.md
  - 0033_terminal_command_delivery.md
- architecture/
  - 0040_hexagonal_baseline.md
  - 0041_public_contracts.md
  - 0042_error_handling_and_redaction.md
  - 0043_debug_gating.md
  - 0044_audit_and_dod.md
- domain/
  - 0050_final_domain_map.md
  - 0051_ui_terms_and_status.md
  - 0052_payment_lifecycle.md
  - 0053_reporting_boundary.md
- stack/
  - 0060_laravel_rules.md
  - 0061_go_rules.md
  - 0062_aws_baseline.md
- 0099_changelog.md

## Package Content Classification

`docs/01_standards` contains only the canonical AI_RULES standards package.

Canonical standards:
- 0001_index.md
- 0002_decision_policy.md
- 0003_gpt_bootstrap_prompt.md
- 0004_session_start_protocol.md
- 0005_handoff_template.md
- 0006_final_review_checklist.md
- 0007_ai_usage_guide.md
- 0008_ai_personalization_profile.md
- core/
- workflow/
- output/
- architecture/
- domain/
- stack/
- 0099_changelog.md

Topic-specific DoD, workflow, and blueprints live in `docs/03_blueprints/`.
Legacy and historical material lives in `docs/99_archive/`.

## Non-Negotiable Behavior
- Do not invent facts.
- Do not claim progress without proof.
- Do not jump directly into implementation when the blueprint is unclear.
- Do not make output formatting more important than domain correctness.
- Do not treat a proposal as completed execution.
- Do not continue large implementation if session capacity is below the threshold in `workflow/24-session-capacity-policy.md`.

## Conflict Reminder
If there is a conflict, read `0002_decision_policy.md` and then:
1. prioritize P0
2. prioritize the more specific rule
3. prioritize the domain when the conflict concerns business meaning
4. if the data is insufficient, stop at GAP
