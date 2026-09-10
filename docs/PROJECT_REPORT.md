# Laporan Proyek — JobFinance

## Ringkasan

JobFinance adalah demo aplikasi job costing, billing, dan akuntansi untuk perusahaan jasa/logistik (Laravel 12, Blade, Vite, MySQL). Laporan ini merangkum hasil seluruh fase brief, termasuk material tambahan fase terakhir: dashboard per-role, penguatan keuangan (kurs invoice, reimbursement valas), SOA email, referensi quotation pada biaya job, diferensiasi Finance vs Finance Manager, dan Coretax select-preview.

Sumber kebenaran permission: `config/jobfinance.php` (di-sync ke database oleh `RolePermissionSeeder`). RBAC lengkap: [`RBAC_MATRIX.md`](RBAC_MATRIX.md).

## Arsitektur

- **Enforcement otorisasi berlapis**: middleware `can:<permission>` pada route, `Policy` pada aksi spesifik (approval, konversi, open/cancel, biaya), dan `@can` hanya untuk lapisan UX.
- **Servis transaksional**: `JobClosingService`, `JobCostService`, `ReimbursementService`, `QuotationService`, `JournalService` memakai `DB::transaction` + `lock_version` (optimistic concurrency) + audit `ActivityLog` (action, module, record_id, before/after).
- **Nilai presisi**: seluruh nominal `DECIMAL(18,2)` dihitung dengan `App\Support\Money` (Brick Math), skala 2.
- **Snapshot historis**: quotation → snapshot job → snapshot closing/invoice → customer snapshot, sehingga histori tidak berubah walau data master diedit.
- **Kurs**: `WeeklyPricing` menyimpan kurs aktif; quotation valas, biaya job, dan invoice memakai kurs terkunci. Closing valas mendukung override kurs opsional (hanya non-IDR).
- **Tes**: feature test SQLite in-memory terisolasi dari MySQL kerja (source of truth untuk QA otomatis).

## Ringkasan modul

| Modul | Keterangan |
| --- | --- |
| Quotation → Job Order | Draft → Submitted → Approved → Converted; snapshot, revisi, approval, kurs valas, auto-seed biaya |
| Biaya Job | Temporary (ditagihkan tanpa profit) & Provision (modal / jual); Draft → Final; referensi quotation + item |
| Shipment status | Booked → In Progress → Departed → Arrived → SPJM → SPPB → DO Process → Completed; timeline historis |
| Closing | Kunci snapshot + invoice + jurnal seimbang; pajak; override kurs valas; audit |
| Invoice & pembayaran | Status issued → partially_paid → paid; anti-overpayment; saldo terkunci; SOA email |
| Reimbursement | pending → approved/rejected → paid; jurnal otomatis; job/vendor/currency/kurs/lampiran |
| Jurnal & laporan | GL, Neraca Saldo, Neraca, Laba Rugi, Arus Kas, Profit per Job, Profit Bulanan, reversal |
| Statement of Account | Running balance, aging bucket, filter "hanya belum lunas", kirim email bila `email.manage` |
| Dashboard per-role | Pipeline quotation, shipment berjalan, piutang jatuh tempo, top job, widget admin |
| Coretax | Ekspor faktur PPN ke XML; galeri pilih faktur + pratinjau XML sebelum unduh |
| Akses | Users (admin), activity log, RBAC 8 role, users.view read-only utk Finance Manager |

## Role dan diferensiasi

- **Finance (eksekutor)**: biaya, closing, invoice, pembayaran, jurnal, reimbursement, laporan, email.
- **Finance Manager (supervisor)**: seluruh hak Finance ditambah `users.view` (daftar pengguna read-only).
- **Sales Manager / Sales**: quotation pipeline, approval (hanya manager), jobs, pricing.
- **Operational / Customer Service**: operasional job, status pengiriman, dokumen; CS melihat job miliknya.
- **Management**: dashboard saja.
- **Super Admin**: `*` (seluruh permission).

## Material fase terakhir

- Migrasi baru (target database remote):
  - `2026_09_10_000008_create_reimbursements_table` (sudah ada) + `2026_09_10_000011_augment_reimbursements` (job_id, vendor_id, currency, kurs, lampiran).
  - `2026_09_10_000012_add_quotation_reference_to_job_costs` (quotation_id, quotation_item_id).
  - Sebelumnya: `2026_09_10_000009_add_invoice_exchange_rate`, `000010_add_shipment_status_tracking`.
- Endpoint baru:
  - `GET /invoices/coretax` (pilih faktur PPN), `GET /invoices/{invoice}/coretax/preview`.
  - `POST /reports/statement-of-account/{customer}/email`.
  - `GET /reimbursements/{reimbursement}/attachment`.
- Perubahan permission: Finance Manager + `users.view`.
- Field request baru: `exchange_rate_override` (closing), reimbursement `job_id/vendor_id/currency/exchange_rate/attachment`.

## Pengujian

- Total suite: **128 pengujian feature/unit (1.684 asersi), seluruhnya hijau** (SQLite in-memory).
- Cakupan baru: ExchangeRateInvoice (6), Reimbursement (8), StatementOfAccount (6, termasuk email + unpaid), Coretax (5, termasuk select-preview), DashboardWidgets (6), ShipmentStatus (5), JobQuotationCharges (3), Workspace (8, termasuk users.view Finance Manager).
- `vendor/bin/pint` green; `php artisan view:cache` / `npm run build` sebagai verifikasi deployment.

## Catatan operasional

- Database kerja (MySQL remote) dapat tidak terjangkau; jalankan `php artisan migrate --force` saat koneksi pulih untuk menerapkan migration 000008–000012.
- `.env.local` berisi kredensial DB kerja dan tetap dibiarkan apa adanya (bukan untuk version control production).