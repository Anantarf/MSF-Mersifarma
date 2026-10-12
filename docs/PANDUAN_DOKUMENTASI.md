# Panduan Dokumentasi MSF

Dokumentasi dibagi antara **kondisi aplikasi saat ini** dan **spesifikasi/desain**. README mencatat fitur dan verifikasi yang sudah dilakukan; dokumen di `docs/` menjelaskan aturan, kontrak, keputusan, dan rencana.

## Mulai dari Sini

| Kebutuhan | Dokumen |
|---|---|
| Menjalankan aplikasi, akun demo, fitur, dan hasil verifikasi | [`../README.md`](../README.md) |
| Aturan kerja dan batasan engineering repo | [`../AGENTS.md`](../AGENTS.md) |
| Memahami tujuan produk dan aturan bisnis | [`PRD.md`](PRD.md) |
| Memahami arsitektur dan alur sistem | [`DESIGN.md`](DESIGN.md) |
| Menelusuri endpoint, payload, dan error API | [`API_CONTRACT.md`](API_CONTRACT.md) |
| Memeriksa tabel, relasi, constraint, dan index | [`DATABASE_SCHEMA.md`](DATABASE_SCHEMA.md) |
| Memahami service, transaksi, dan perpindahan status | [`TDD.md`](TDD.md) |
| Menjalankan skenario dan memeriksa kriteria penerimaan | [`TEST_PLAN.md`](TEST_PLAN.md) |

## Dokumen Pendukung

| Dokumen | Kegunaan |
|---|---|
| [`DECISIONS.md`](DECISIONS.md) | Asumsi, trade-off, dan keputusan yang disetujui |
| [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md) | Urutan kerja dan delivery gate |
| [`MANAGERIAL_PLAN.md`](MANAGERIAL_PLAN.md) | Rencana tambahan untuk fitur manajerial |
| [`MSF_SEEDER_BUSINESS_SCENARIO_PEDIATRI.md`](MSF_SEEDER_BUSINESS_SCENARIO_PEDIATRI.md) | Skenario data sintetis untuk seeder |
| [`README_REQUIREMENTS.md`](README_REQUIREMENTS.md) | Checklist penyusunan README |
| [`presentation_deck_blueprint.md`](presentation_deck_blueprint.md) | Kerangka presentasi proyek |
| [`MSF_Change_Impact_v1.8_Proposal.md`](MSF_Change_Impact_v1.8_Proposal.md) | Proposal historis; bukan bukti fitur telah diterapkan |

## Prioritas Sumber

Aturan studi kasus PDF menjadi sumber utama. Jika ada konflik, gunakan `API_CONTRACT.md` untuk HTTP, `DATABASE_SCHEMA.md` untuk persistence, `PRD.md` untuk aturan bisnis/UX, `TDD.md` untuk implementasi, dan `DESIGN.md` untuk gambaran terintegrasi. `DECISIONS.md` hanya mencatat asumsi yang telah disetujui. Jangan mengubah aturan material secara diam-diam.

Detail invariant dan acceptance gate berada di dokumen sumber masing-masing, terutama `PRD.md`, `API_CONTRACT.md`, `DATABASE_SCHEMA.md`, dan `TEST_PLAN.md`; indeks ini sengaja tidak menyalin ulang isinya.

## Status dan Bukti

Gunakan `README.md` sebagai satu-satunya ringkasan kondisi implementasi, hasil command terbaru, dan lokasi evidence. Angka test atau status pada proposal dan dokumen historis tidak boleh dianggap sebagai hasil terkini. Automated test, smoke test UI, dan skenario manual adalah jenis bukti berbeda dan harus dilaporkan terpisah.
