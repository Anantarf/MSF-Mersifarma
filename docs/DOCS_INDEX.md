# Dokumentasi MSF

Dokumen ini adalah peta baca untuk proyek Mobile Sales Force PT Mersifarma. Isinya memisahkan aturan studi kasus, keputusan desain, kontrak API, struktur data, dan bukti pengujian.

## Status Singkat

| Area | Status |
|---|---|
| Backend API wajib | Selesai dan tercakup automated test |
| Frontend utama | Selesai untuk Target Dokter, Jadwal Kunjungan, Pelaksanaan Kunjungan |
| Automated test terakhir | 65 passed, 239 assertions |
| Manual evidence | Tersedia di `docs/evidence/ui-smoke` |
| Batasan | Browser click-through penuh dan MySQL concurrency nyata tetap perlu diverifikasi jika diminta sebagai bukti akhir |

## Urutan Baca

| Dokumen | Isi |
|---|---|
| [`../README.md`](../README.md) | Cara menjalankan proyek, akun demo, endpoint, hasil test, dan catatan demo |
| [`../AGENTS.md`](../AGENTS.md) | Aturan kerja engineering untuk proyek ini |
| [`PRD.md`](PRD.md) | Scope produk, aktor, aturan bisnis, dan UX yang disetujui |
| [`API_CONTRACT.md`](API_CONTRACT.md) | Delapan endpoint wajib, payload, response, status code, dan error code |
| [`DATABASE_SCHEMA.md`](DATABASE_SCHEMA.md) | Tabel, relasi, constraint, index, dan catatan MySQL |
| [`TDD.md`](TDD.md) | Service boundary, workflow transaksi, locking, dan state transition |
| [`TEST_PLAN.md`](TEST_PLAN.md) | Skenario wajib PDF, negative case, dan acceptance gate |
| [`DECISIONS.md`](DECISIONS.md) | ADR, asumsi, trade-off, dan batasan yang diterima |
| [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md) | Catatan urutan implementasi dan delivery gate |
| [`README_REQUIREMENTS.md`](README_REQUIREMENTS.md) | Checklist isi README final |
| [`MANAGERIAL_PLAN.md`](MANAGERIAL_PLAN.md) | Catatan tambahan untuk mode monitoring manajerial |
| [`MSF_Change_Impact_v1.8_Proposal.md`](MSF_Change_Impact_v1.8_Proposal.md) | Proposal historis v1.8; bukan sumber klaim implementasi |

## Prioritas Aturan

1. Aturan studi kasus PDF tetap sumber utama.
2. `API_CONTRACT.md` dipakai untuk kontrak HTTP.
3. `DATABASE_SCHEMA.md` dipakai untuk struktur data dan constraint.
4. `PRD.md` dipakai untuk aturan bisnis dan pengalaman pengguna.
5. `TDD.md` dipakai untuk workflow service dan transaksi.
6. `DECISIONS.md` menjelaskan asumsi yang tidak eksplisit di PDF.

Jika ada konflik, gunakan aturan studi kasus dulu. Jangan mengubah invariant seperti approval satu tingkat, `plan_id=null`, atau satu Actual final per dokter/tanggal hanya demi menyederhanakan implementasi.

## Ringkasan Invariant

- Approval Call List selalu satu tingkat langsung: MR ke DM, DM ke RSM, RSM ke MM.
- Call Plan hanya dibuat dari Call List Approved dan dokter anggota List.
- Actual final wajib punya dokter valid, foto, tanda tangan/stempel, minimal satu produk, dan waktu masuk/keluar yang valid.
- `plan_id` boleh `null`; Direct Actual tanpa Plan tetap bagian dari kontrak wajib.
- Satu dokter hanya boleh memiliki satu Actual final pada tanggal yang sama, lintas MR dan lintas mode.
- UI harus Planned-first, dengan opsi sekunder `Kunjungan di Luar Rencana`.
- Frontend membantu pengguna, tetapi backend tetap sumber kebenaran authorization, ownership, dan uniqueness.

## Catatan Kejujuran

Dokumen di folder ini menjelaskan spesifikasi dan keputusan. Klaim proyek yang benar-benar dijalankan ada di `README.md` dan hasil command terakhir. Jika angka test atau evidence berbeda, gunakan data terbaru dari command, bukan angka lama di dokumen historis.
