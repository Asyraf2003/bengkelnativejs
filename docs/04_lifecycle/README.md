# 04_lifecycle

Runtime records — rekam jejak operasional sistem yang terus bertambah.

## Subfolder

| Folder | Isi |
|---|---|
| `error_log/` | 29 bug dan security findings individual. Tiap issue satu file. |
| `handoff/` | Session recovery notes sesi aktif. Naming: `NNNN_topic_handoff.md`. |

## Gunakan Untuk

- `error_log/` untuk bug atau security finding yang harus dilacak sampai tuntas
- `handoff/` untuk progress sesi, proof, changed files, blocker, dan next step

## Jangan Gunakan Untuk

- keputusan permanen
- blueprint aktif
- dokumen legacy yang sudah selesai

## Catatan

Jika handoff sudah tidak relevan untuk kerja aktif, pindahkan ke `docs/99_archive/handoff/`.

## Aturan

- `error_log/` tidak boleh dihapus atau diubah statusnya tanpa proof dan owner acceptance.
- `handoff/` adalah untuk sesi terbaru saja — setelah selesai, pindah ke `docs/99_archive/handoff/`.
