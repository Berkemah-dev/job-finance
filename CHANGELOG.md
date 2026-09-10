# Release Notes

## JobFinance Demo 1.3.0 — 2026-09-10

- Override kurs manual pada closing valas (hanya non-IDR, wajib > 0, audit log) dan kartu kurs pada tampilan closing.
- Reimbursement diperluas: tautan ke job order dan vendor, mata uang + kurs (IDR vs valas), lampiran file, unduhan terproteksi, dan konversi jurnal ke IDR.
- Statement of Account: filter "hanya belum lunas" dan pengiriman email berisi ringkasan periode (wajib permission `email.manage`).
- Biaya job tercantum referensi quotation + item (nomor quotation, item ke-N, badge di daftar biaya).
- Diferensiasi role: **Finance Manager = Finance + `users.view`** (daftar pengguna read-only) di `config/jobfinance.php`, matriks RBAC diperbarui.
- Coretax select-preview: galeri faktur PPN layak ekspor (filter pencarian/status) + pratinjau XML anonim tanpa membuat log; unduh tetap mencatat `invoice.coretax.exported`.
- 5 pengujian Coretax (termasuk preview tanpa log dan 403 non-finance); total 128 pengujian / 1.684 asersi hijau.
- Migrasi baru: `2026_09_10_000011_augment_reimbursements`, `2026_09_10_000012_add_quotation_reference_to_job_costs` (terapkan ke basis data kerja saat koneksi pulih).
- Laporan proyek: [`docs/PROJECT_REPORT.md`](docs/PROJECT_REPORT.md).

## JobFinance Demo 1.2.0 — 2026-09-10

- Pengamanan finansial pada sisi backend: data sensitif (unit cost, total cost, profit) hanya dikirim ke pengguna dengan `financial.view`.
- Penegasan izin rute approver quotation (`quotations.approve`) dan konversi dua izin; alias rute `/job-orders`.
- Matriks RBAC terdokumentasi (`docs/RBAC_MATRIX.md`).
- Status pengiriman Job Order (Booked → In Progress → Departed → Arrived → SPJM → SPPB → DO Process → Completed) dengan timeline historis, filter daftar, dan audit log.
- Kolom migration `job_shipment_statuses` + `jobs.shipment_status*` (diterapkan pada basis data target).
- 5 pengujian baru Status Pengiriman; total 111 pengujian / 1.464 asersi hijau.

## JobFinance Demo 1.1.0 — 2026-09-10

- Statement of Account dengan aging 0–30/31–60/61–90/di atas 90 hari.
- Modul Reimbursement (request → approved/rejected → paid) dengan jurnal otomatis.
- Biaya Job Order berisi otomatis dari rincian quotation saat job dibuka.
- Invoice dan snapshot closing menyimpan mata uang serta kurs; tampilan menampilkan ekuivalen IDR.
- Laporan Profit Bulanan berbasis snapshot closing historis.
- Ekspor faktur PPN ke XML Coretax (seller/buyer/DPP/PPN/ringkasan).

## JobFinance Demo 1.0.0 — 2026-09-08

- Alur lengkap Customer sampai laporan keuangan.
- RBAC empat role, administrasi pengguna, dan activity log.
- Closing transaksional, invoice, pembayaran, jurnal otomatis, penyesuaian, dan reversal.
- Buku Besar, Neraca Saldo, Neraca, Laba Rugi, Arus Kas, Profit per Job, serta dashboard bulanan.
- Seeder demo idempoten, factory transaksi, validasi, authorization, dan QA otomatis.

## [Unreleased](https://github.com/laravel/laravel/compare/v12.0.0...master)

## [v12.0.0 (2025-??-??)](https://github.com/laravel/laravel/compare/v11.0.2...v12.0.0)

Laravel 12 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
