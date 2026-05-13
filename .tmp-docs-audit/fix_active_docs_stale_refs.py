from pathlib import Path

replacements = {
    "docs/02_architecture/adr/0015-note-operational-status-open-close-editable-partial-payment.md":
        "docs/02_architecture/adr/0015_note_operational_status_open_close_editable_partial_payment.md",

    "docs/03_blueprints/v2/note_finance/2026-04-29-note-finance-stabilization-blueprint.md":
        "docs/03_blueprints/finance/0001_note_finance_stabilization.md",
    "docs/03_blueprints/v2/note_finance/2026-04-29-note-finance-current-projection-addendum.md":
        "docs/03_blueprints/finance/0002_note_finance_stabilization_addendum.md",
    "docs/03_blueprints/v2/note_finance/2026-05-06-error-log-finance-residual-implementation-blueprint.md":
        "docs/03_blueprints/finance/0003_finance_residual.md",
    "docs/03_blueprints/v2/note_finance/2026-05-12-note-revision-refund-ledger-blueprint.md":
        "docs/03_blueprints/finance/0006_note_revision_refund_ledger.md",
    "docs/03_blueprints/v2/note_finance/2026-05-12-note-revision-refund-ledger-dod.md":
        "docs/03_blueprints/finance/0007_note_revision_refund_ledger_dod.md",
    "docs/03_blueprints/v2/note_finance/2026-05-12-note-revision-refund-ledger-workflow.md":
        "docs/03_blueprints/finance/0008_note_revision_refund_ledger_workflow.md",

    "docs/03_blueprints/v2/feature_continuation/00-blueprint.md":
        "docs/03_blueprints/feature_continuation/0001_blueprint.md",
    "docs/03_blueprints/v2/feature-continuation":
        "docs/03_blueprints/feature_continuation",
    "docs/03_blueprints/v2/feature_continuation":
        "docs/03_blueprints/feature_continuation",

    "docs/04_lifecycle/error-log":
        "docs/04_lifecycle/error_log",
    "docs/error_log":
        "docs/04_lifecycle/error_log",
}

# Map old 3-digit + hyphen error log references to current 4-digit + snake_case paths.
error_log_dir = Path("docs/04_lifecycle/error_log")
for path in sorted(error_log_dir.glob("*.md")):
    if path.name == "README.md":
        continue

    stem = path.stem
    number, _, snake_title = stem.partition("_")
    if not number or not snake_title:
        continue

    number3 = str(int(number)).zfill(3)
    hyphen_title = snake_title.replace("_", "-")
    current = path.as_posix()

    old_candidates = [
        f"docs/04_lifecycle/error_log/{number3}-{hyphen_title}.md",
        f"docs/04_lifecycle/error_log/{number}-{hyphen_title}.md",
        f"docs/04_lifecycle/error-log/{number3}-{hyphen_title}.md",
        f"docs/04_lifecycle/error-log/{number}-{hyphen_title}.md",
        f"docs/error_log/{number3}-{hyphen_title}.md",
        f"docs/error_log/{number}-{hyphen_title}.md",
    ]

    for old in old_candidates:
        replacements[old] = current

changed = []

for path in sorted(Path("docs").rglob("*.md")):
    if "docs/99_archive" in path.as_posix():
        continue

    text = path.read_text(encoding="utf-8")
    original = text

    for old, new in replacements.items():
        text = text.replace(old, new)

    if text != original:
        path.write_text(text, encoding="utf-8")
        changed.append(path.as_posix())

print("changed_files:")
for item in changed:
    print(item)
