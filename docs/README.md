# Hyperpos Documentation Index

## Purpose

Direktori ini berisi seluruh dokumentasi teknis sistem Hyperpos — aturan kerja AI, keputusan arsitektur, blueprint implementasi, lifecycle records, dan audit.

Tujuan index ini: memudahkan manusia atau AI agent menemukan dokumen yang tepat tanpa harus baca semua.

## Fast Start

Baca dalam urutan ini:

    docs/01_standards/0007_ai_usage_guide.md
    docs/01_standards/0001_index.md
    Blueprint aktif untuk scope saat ini
    Output lokal terbaru dari operator

## Source Of Truth Priority

Gunakan urutan ini ketika dokumen saling bertentangan:

1. Output lokal dari operator (tertinggi)
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. Blueprint aktif di `docs/03_blueprints`
5. Handoff terbaru di `docs/04_lifecycle/handoff`
6. Archive di `docs/99_archive`
7. General model knowledge (terendah)

## Placement Guide

Pakai aturan ini untuk menaruh dokumen di tempat yang benar:

| Jenis dokumen | Tempat | Isi |
|---|---|---|
| Standards / aturan wajib | `docs/01_standards` | Rule global AI, decision policy, output rules, domain map, stack rules |
| ADR / keputusan permanen | `docs/02_architecture/adr` | Keputusan arsitektur, domain, lifecycle, reporting, data representation |
| Blueprint / desain aktif | `docs/03_blueprints` | Scope, design, DoD, workflow, test matrix, implementation order |
| Error log / finding | `docs/04_lifecycle/error_log` | Bug, security finding, lifecycle issue, satu issue satu file |
| Handoff aktif | `docs/04_lifecycle/handoff` | Progress terakhir, proof, changed files, blocker, next step |
| Audit report | `docs/05_audits` | Laporan audit berdiri sendiri, ringkasan proof, coverage, temuan |
| Legacy / historical | `docs/99_archive` | Handoff lama, blueprint lama, standards lama, dokumen superseded |

Kalau ragu:

1. Keputusan permanen masuk ADR.
2. Desain yang masih dikerjakan masuk blueprint.
3. Hasil kerja sesi masuk handoff aktif.
4. Riwayat lama yang tidak lagi aktif masuk archive.

## Directory Map

### `docs/01_standards`

Aturan wajib untuk semua sesi kerja AI di repo ini.

Gunakan untuk: zero assumption rule, blueprint-first rule, one active step rule,
proof and progress rule, response structure, handoff policy, architecture boundary,
public contract protection, redaction rule, final domain map, stack rules.

Tidak untuk: bug notes, feature status, commit hash, temporary local state.

### `docs/02_architecture/adr`

Permanent decision records. Sequential numbered `NNNN_snake_title.md`.

Gunakan untuk: keputusan arsitektur, keputusan domain, lifecycle decisions,
reporting boundary, data representation.

Jika keputusan berubah: buat ADR baru yang supersede, jangan edit ADR lama.

### `docs/03_blueprints`

Design blueprints + DoD + Workflow per topik. Hanya untuk scope aktif atau recent scope yang masih dikerjakan.

Isi yang cocok:

- scope in / scope out
- problem statement
- design options dan keputusan desain
- DoD / test matrix / implementation order
- workflow CLI dan urutan eksekusi

Tidak untuk:

- keputusan permanen yang harus jadi ADR
- catatan harian sesi
- hasil uji final yang lebih cocok di handoff

Diorganisir dalam subfolder:

- `security/` — ADR-0019 s/d ADR-0023 blueprints, DoD, workflow
- `finance/` — note finance, residual, revision-refund-ledger
- `reporting/` — report export, reporting execution
- `seeder/` — legacy-to-clean
- `mobile/` — mobile API
- `error_log_remediation/` — error log remediation docs
- `feature_continuation/` — feature continuation blueprint

Naming: `NNNN_topic_name.md` (blueprint), `NNNN_topic_name_dod.md` (DoD), `NNNN_topic_name_workflow.md` (Workflow).

### `docs/04_lifecycle`

Runtime records.

`error_log/` — individual bug/security findings, numbered `NNNN_snake_title.md`

`handoff/` — session recovery notes untuk sesi aktif/terbaru

Handoff cocok untuk:

- ringkasan progress
- proof dan test output
- file yang berubah
- blocker dan risiko
- prompt pembuka sesi berikutnya

Handoff tidak cocok untuk:

- keputusan permanen
- blueprint aktif
- catatan yang sudah pasti historical

Kalau sesi selesai, pindahkan handoff ke `docs/99_archive/handoff/`.

### `docs/05_audits`

Formal audit records dengan numbered snake_case filename `NNNN_topic_name.md`.

Audit cocok untuk:

- ringkasan audit
- coverage summary
- proof of work
- rekomendasi dan risiko

Audit bukan pengganti handoff, dan bukan blueprint.

### `docs/99_archive`

Semua dokumen legacy, superseded, dan historical. Copy penuh, tidak dimodifikasi.

Jangan simpan work aktif di sini. Kalau sesuatu masih harus dikerjakan, simpan di `docs/03_blueprints` atau `docs/04_lifecycle/handoff`.

- `standards/` — old standards docs
- `blueprints/` — blueprint v1
- `dod/` — dod v1
- `handoff/` — semua handoff lama (step-based, ui, v2, kotlin, dll)

## Naming Convention

| Jenis | Format | Contoh |
|---|---|---|
| ADR | `NNNN_snake_title.md` | `0019_note_access_boundary_cashier_date_window_and_transaction_capability_enforcement.md` |
| Blueprint | `NNNN_topic_name.md` | `0003_finance_residual.md` |
| DoD | `NNNN_topic_name_dod.md` | `0004_finance_residual_dod.md` |
| Workflow | `NNNN_topic_name_workflow.md` | `0005_finance_residual_workflow.md` |
| Error log | `NNNN_snake_title.md` | `0009_cashiers_can_rewrite_closed_paid_notes_via_workspace_update.md` |
| Audit record | `NNNN_topic_name.md` | `0002_error_log_solution_and_adr_coverage_summary.md` |
| Handoff aktif | `NNNN_topic_handoff.md` | `0001_scope_handoff.md` |
| Folder | `NN_prefix_snake_case` for L1, `snake_case` for subfolders | `01_standards/`, `error_log/` |

## Promotion Rule

Jika handoff mengandung keputusan yang harus permanen:

1. Buat atau update ADR.
2. Referensikan handoff sebagai evidence.
3. Tandai handoff sebagai historical.
4. Jangan biarkan keputusan permanen hanya ada di handoff.
