# AUDIT FITUR — JobFinance (Logistik / Freight Forwarder)

- **Tanggal audit**: 11 Sep 2026
- **Base**: commit `519d5b1` ("ok") — tree bersih (HEAD)
- **Metode**: penelusuran source code (routes, controller, service, model, migration, view, test) + pengecekan relasi/pemakaian. Status tracker/dok tidak dipercaya mentah; setiap klaim diverifikasi dari file.
- **Legenda**: DONE = end-to-end berfungsi; PARTIAL = ada tetapi perlu penyempurnaan; NOT IMPLEMENTED = belum ada; BROKEN = ada tetapi rusak; MERGE = digabung / kandidat gabung; REMOVE = kandidat hapus.

---

## Ringkasan Eksekutif

1. **Tidak ada duplikasi sistem besar** yang tertangkap untuk: Activity Log (satu tabel `activity_logs` + satu writer `MasterDataService::log`), Dashboard (satu controller + satu view, widget digate per permission), Vendor (satu tabel `vendors` + pivot `vendor_categories` untuk 4 kategori), PDF (satu engine dompdf, 5 template), Job Document (satu `JobDocumentController` untuk CS & Operation, dibedakan permission), Calculator (volume & CBM satu halaman).
2. **Bug/kerusakan nyata** (harus diperbaiki sebelum lanjut):
   - `B1` Update Customer Contact → 500 (kolom `lock_version` tidak pernah dibuat, tapi model/controller memakainya).
   - `B2` DO Selesai → method controller `confirmDo` tidak ada; kolom `do_confirmed_at` tidak ada.
   - `B3` Riwayat shipment status ditulis ke `job_status_history` (salah), tabel `job_shipment_statuses` + model `JobShipmentStatus` tidak dipakai (dead code).
   - `B4` Job Document upload/download/destroy memakai `Storage::disk('private')` yang tidak terkonfigurasi → error runtime; tanpa test.
   - `B5` `.env.example` tidak ada, tetapi dipakai CI (`tests.yml`), `composer.json`, dan 2 dok → CI rusak.
   - `B6` Nomor migrasi `2026_09_10_000006` dipakai dua file (urutan/sorting ambigu).
3. **Belum tersentuh scope**: TPS Master Air/Sea (36), Invoice asli dikirim (41), DNP & SK Pabean (39), Invoice/SOA PDF, log email SOA, .env.example, backup/monitoring, UAT, user guide.
4. **Kandidat cleanup** (menunggu persetujuan, tidak dieksekusi di tahap audit): alias route `job-orders.*`, redirect `/documents`→`/dokumen-job`, `welcome.blade.php`, permission tak terpakai `roles.manage`/`settings.manage`.

---

## AUDIT SCOPE 1–65

| No | Module | Feature | Existing Implementation | FE | BE | DB | RBAC | Integrasi | Status | Action |
| -- | ------ | ------- | ----------------------- | -- | -- | -- | ---- | --------- | ------ | ------ |
| 1 | Foundation | RBAC 7 role | `config/jobfinance.php:39-67` (8 role: super-admin, finance, finance-manager, sales-manager, sales, operational, customer-service, management); tabel `roles`/`permissions`/`permission_role` `2026_09_08_000001`; Gate otomatis `AppServiceProvider.php:24-26`; policies Job/Quotation/JobCost/User | OK | OK | OK | OK | OK | DONE | Tambah nama role `operation` ≠ `operational` (misal label) agar cocok scope; `roles.manage`/`settings.manage` tidak dipakai |
| 2 | Foundation | Activity log global | Tabel `activity_logs` (+module/record/before/after, `2026_09_09_000001`); writer tunggal `MasterDataService::log` (`MasterDataService.php:149-162`); dipanggil 11 service + 3 controller + `AuthenticationService`; view `/activity` (`routes/web.php:171`, `AccessController.php:51-76`) | OK | OK | OK | `activity.view` | OK | DONE | Tidak ada log finance terpisah — sudah satu engine |
| 3 | Foundation | Dashboard role-based | Satu route `/dashboard` (`web.php:165`) + `DashboardController` + `view dashboard.blade.php`; widget digate `Gate::allows('financial.view')` dll di `DashboardService.php:22-92`; semua data query nyata (Job counts, cost, invoice, closing snapshot, ETA/ETD) | OK | OK | OK | OK | OK | DONE | Sudah satu dashboard; No.21 & No.35 = widget |
| 4 | Customer | Auto customer code | `config/operations.php:11`; `DocumentNumberService::nextYear` (`DocumentNumberService.php:25-37`) → `CUS-2026-00001`; dipakai `CustomerController::store:41-42` | OK | OK | OK | `customers.manage` | OK | DONE | — |
| 5 | Customer | Master customer (nama, email, telp, NPWP, alamat) | Tabel `customers` (`2026_09_08_000002:11-26`): code,name,contact_name,email,phone,tax_number,address; validasi `CustomerRequest.php:27` (NPWP 15-16 digit) | OK | OK | OK | OK | OK | DONE | Alamat invoice = satu field `address`; tambah `invoice_address` opsional bila perlu |
| 6 | Customer | Default payment terms | Kolom `default_payment_terms` (`2026_09_09_000002:12`); opsi `config/operations.php:9`; otomatis ke quotation (`QuotationService.php:42-44`) | OK | OK | OK | OK | OK | DONE | — |
| 7 | Customer | Upload NPWP & NIB | Kolom `npwp_file`/`nib_file` + `customer_documents` (`2026_09_09_000002:13-27`); `CustomerController::handleUploads:111-129`; validasi mime/size `CustomerRequest.php:29-30`; download/destroy `CustomerDocumentController.php` | OK | OK | OK | OK | OK | DONE | Tidak punya test upload (lihat No.22) |
| 8 | Customer | Master shipper/consignee | `customer_contacts` (`2026_09_09_000002:29-40` + `2026_09_11_000001:17-22`); CRUD `CustomerContactController`; inline editor `customers/form.blade.php:19-29` | OK | OK | OK | OK | OK | DONE | **B1** update kontak 500 (kolom `lock_version` hilang) |
| 9-12 | Vendor | Shipping Lines / Trucking / Agent Int'l / Agent Nasional | SATU tabel `vendors` (`2026_09_09_000003`): `type` utama + pivot `vendor_categories` (`2026_09_11_000002`); kategori di `config/operations.php:8`; filter `VendorController:24-28` | OK | OK | OK | OK | OK | DONE | Sudah 1 master vendor utk 4 scope; data tidak perlu migrasi |
| 13 | Sales | Quotation CRUD/list/detail | Resource `quotations` except destroy (`web.php:88`); `QuotationController` + `QuotationService` (items, snapshot, duplicate); enum `QuotationStatus` (draft/submitted/revision/approved/rejected/converted) | OK | OK | OK | `quotations.manage` | OK | DONE | — |
| 14 | Sales | Approve quotation (SM) | Route `/approve|reject|revise` (`web.php:91-93`, `can:quotations.approve`); `QuotationService:73-121`; policy `QuotationPolicy:36-49` | OK | OK | OK | OK | OK | DONE | — |
| 15 | Sales | Kalkulator pajak | Route `calculators.tax` (`web.php:84`) + API `:87`; `CalculationService::tax:101-116` | OK | OK | N/A | `dashboard.view` | OK | DONE | — |
| 16 | Sales | Pricing mingguan | `weekly_pricings` (`2026_09_09_000004:11-25`) + `effective_until` (`2026_09_11_000004`); `PricingService::activeWeeklyRate:83-93`; CRUD `WeeklyPricingController` | OK | OK | OK | `pricing.manage/view` | OK | DONE | `config weekly_pricing_settings` tidak ada → logika di service (acceptable) |
| 17 | Sales | Kalkulator biaya LCL | Route `calculators.lcl` + API; `CalculationService:122-142` (basis W/M, rate config `lcl.default_rate`); inline panel di form quotation | OK | OK | N/A | `dashboard.view` | OK | DONE | — |
| 18 | Sales | Trucking tariff (asal,tujuan,overweight,20/40FT,LCL) | `trucking_prices` (`2026_09_09_000004:27-44` + efektif `2026_09_11_000004`): port_origin,destination,overweight,container_type(20/40/lcl),price; `PricingService::findTruckingPrice:95-105`; pengisi di quotation | OK | OK | OK | `pricing.manage/view` | OK | DONE | Biaya per container via baris `container_type`, bukan kolom terpisah |
| 19 | Sales | Volume calculator (P×L×T/6000) | `CalculationService::volumeWeight:18-23` (divisor 6000); halaman `calculators/volume-weight.blade.php` | OK | OK | N/A | OK | OK | DONE | Sudah satu halaman dgn CBM |
| 20 | Sales | CBM calculator (/1.000.000) | `CalculationService::cbm:25-30` (divisor 1.000.000); tampil di halaman yang sama dgn volume | OK | OK | N/A | OK | OK | DONE | Tidak ada halaman terpisah |
| 21 | CS | Summary total Job (waiting/approved/active/done) | Widget `DashboardService::widgets:69-73` (pipeline quotation) & `:74-79` (shipment/ETD/ETA/myOpenJobs) per role | OK | OK | N/A | per role | OK | DONE | Sudah widget dashboard tsb No.3 |
| 22 | CS | Upload default BL/AWB, CI, Packing List | `job_documents` + `document_types` (`2026_09_11_000005`); `JobDocumentController::store`; form di `jobs/show.blade.php`; route `web.php:105-107` | OK | OK | OK | `jobs.view/manage` | PARTIAL | **BROKEN** | `Storage::disk('private')` tidak dikonfigurasi (`config/filesystems.php`) → runtime error; tambah disk + test |
| 23 | CS | Master document type | Resource `document-types` (`web.php:109`); `DocumentTypeController` + `DocumentType::active()` | OK | OK | OK | `jobs.manage` | OK | DONE | Sesuai desain "konfigurasi", bukan modul upload terpisah |
| 24 | CS | Generate SK DO / Surat Jalan / Tanda Terima | `OperationalDocumentController::suratJalanPdf:118-127`, `tandaTerimaPdf:129-138`, `skDoPdf:140-149` (dompdf); view `documents/pdf/*`; preview shell `documents/pdf-preview.blade.php` | OK | OK | N/A | `jobs.view` | OK | DONE | Teruji (`OperationalDocumentTest`) |
| 25 | CS | Input BL/AWB/Shipping Instr/Booking Conf | Field job: `bl_number,hbl_number,awb_number,hawb_number,booking_reference` (`2026_09_10_000007:22-26`); form `jobs/form.blade.php:29-37` | OK | OK | OK | `jobs.manage` | OK | DONE | — |
| 26 | CS | ETA approaching + confirm DO | ETA widget (`DashboardService.php:77`), badge `Job::etaApproaching()` (`Job.php:72-79`); **DO Selesai rusak** (lihat B2) | OK | OK | PARTIAL | OK | PARTIAL | **PARTIAL** | Implementasi `confirmDo` + migrasi `do_confirmed_at/by` |
| 27 | CS | Job list (No Job, Customer, Quote, BL/AWB, Service, Sales, CS, Status, Edit) | `jobs/index.blade.php:16` — semua ada tapi kolom digabung (Quote remark di bawah nomor job; BL/AWB kecil di kolom rute; Sales+CS satu kolom "Penanggung jawab") | OK | OK | N/A | `jobs.view` | OK | DONE | Opsional: pisah kolom "Remark Quote" |
| 28 | Job | Financial summary hanya Finance | `JobController::show:52-59` strip `unit_cost/total_cost/profit` jika !`financial.view`; `QuotationController::show:59-63` set unit_cost null; route finance digate `can:` | OK | OK | N/A | OK | OK | DONE | Enforced backend, bukan hanya hide/show |
| 29 | Job | Shipper & Consignee | `shipper_name/address,consignee_name/address` (`2026_09_10_000007:12-15`); form `jobs/form.blade.php:13-19`; show `jobs/show.blade.php:14` | OK | OK | OK | OK | OK | DONE | — |
| 30 | Job | POL & POD | `pol,pod` (`2026_09_10_000007:16-17`) | OK | OK | OK | OK | OK | DONE | — |
| 31 | Job | ETD & ETA | `etd,eta` (`:18-19`) | OK | OK | OK | OK | OK | DONE | — |
| 32 | Job | BL/AWB + HBL/HAWB | `bl_number,hbl_number,awb_number,hawb_number` (`:22-26`) | OK | OK | OK | OK | OK | DONE | — |
| 33 | Job | Vessel / Flight | `vessel_voyage,flight_number` (`:20-21`) | OK | OK | OK | OK | OK | DONE | — |
| 34 | Job | Qty, Gross Weight, Volume, Commodity | `package_count,gross_weight,volume,container_type` (`:27-30`) + `cargo_description` | OK | OK | OK | OK | OK | DONE | — |
| 35 | Operation | Dashboard Job aktif | Widget `DashboardService.php:74-79` (open jobs, shipment, ETD/ETA) | OK | OK | N/A | per role | OK | DONE | Sudah bagian dashboard tsb No.3 |
| 36 | Operation | Master TPS Air/Sea | **Tidak ada** tabel/model/controller/route/view TPS (grep `tps` hanya false positive) | – | – | – | – | – | **NOT IMPLEMENTED** | Buat TPS master: kota, nama TPS, kode TPS |
| 37 | Operation | Upload Job Document (CS & Operation sama) | Sama dgn No.22: `jobs.documents.*` + `jobs.view` (CS & Operation) | OK | OK | OK | OK | OK | **BROKEN** | Perbaikan sama dgn No.22 (sudah satu sistem, beda permission) |
| 38 | Operation | Shipment status SPJM/SPPB | `config/operations.php:5` (booked,in_progress,departed,arrived,spjm,sppb,do_process,completed); form+timeline `jobs/show.blade.php:38-51`; `JobService::updateShipmentStatus:85-109` | OK | OK | OK | `jobs.manage` | PARTIAL | **PARTIAL** | Riwayat tertulis di `job_status_history` salah (B3); `job_shipment_statuses` mati |
| 39 | Operation | Generate DNP / SK Pabean | Engine dompdf ada (No.24) tapi template DNP & SK Pabean BELUM ada (grep `dnp`/`pabean` = 0) | – | – | N/A | `jobs.view` | – | **NOT IMPLEMENTED** | Tambah 2 template di engine yang sama |
| 40 | Finance | SOA (customer, periode, unpaid only) | Route `web.php:66-68`; `StatementOfAccountService.php` (aging 5 bucket, opening/closing/running, unpaid filter `:38-39`); `reports/soa/*` | OK | OK | OK | `reports.view` | OK | DONE | Binding `{customer}` → archived customer 404 (perlu withTrashed) |
| 41 | Finance | Invoice asli sudah dikirim | **Tidak ada** kolom/status/UI (grep original_invoice/sent_at/received_at/tracking = 0) | – | – | – | – | – | **NOT IMPLEMENTED** | Tambah `original_invoice_status` + sent_at/received_at/notes di flow Invoice/SOA |
| 42 | Finance | Reimbursement / Temporary dari Job | `reimbursements` (`2026_09_10_000008`+`000011`) — employee_id (FK users), job_id, vendor_id, currency, rate, attachment; `ReimbursementService` create/approve/reject/pay (+ jurnal seimbang); teruji | OK | OK | OK | `reimbursements.manage` | OK | DONE | — |
| 43 | Finance | Charges berdasar quotation / Provisional | `job_costs.quotation_id/quotation_item_id` (`2026_09_10_000012`); `JobService::seedQuotationCharges:111-158`; tampilan sumber quotation `costs/index.blade.php:10` | OK | OK | OK | `costs.manage` | OK | DONE | — |
| 44 | Finance | Kurs invoice mingguan, editable manual | `PricingService::activeWeeklyRate`; override `exchange_rate_override` di `JobClosingService:54-59`; `ExchangeRateInvoiceTest` | OK | OK | OK | `jobs.close` | OK | DONE | IDR dilarang override ≠ 1 |
| 45 | Finance | Balance Sheet | `ReportController::balanceSheet:35-40` → `FinancialReportService::balanceSheet:54-63` | OK | OK | N/A | `reports.view` | OK | DONE | Query nyata |
| 46 | Finance | Journal | `JournalController` + `JournalService` (post `:85-107` enforce seimbang, adjustment `:19-43`, reverse `:45-69`); tanpa edit setelah posted (tak ada route update) | OK | OK | OK | `journals.manage` | OK | DONE | — |
| 47 | Finance | Export XML Coretax | `web.php:39-42` select/preview/unduh; `CoretaxService::generate:17-85` (DOM, DPP/PPN/rate/currency); `invoices/coretax.blade.php`; log saat export; teruji | OK | OK | OK | `invoices.manage` | OK | DONE | — |
| 48 | Finance | Finance audit log | `activity_logs` dgn kolom `module` (filter finance) — satu engine global (lih. No.2) | OK | OK | OK | `activity.view` | OK | DONE | Tidak dibuat sistem terpisah |
| 49 | Finance | Email SOA | `Mailable StatementOfAccountMail` + `mail/soa.blade.php`; `StatementOfAccountController::email:34-49` | OK | OK | — | `email.manage` | OK | **PARTIAL** | Kirim berfungsi; log email (recipient/subject/sent_at/status/error/sent_by) BELUM ada |
| 50 | FM | Akses seluruh Finance | `config/jobfinance.php:46-50` finance-manager = finance + `users.view` | OK | OK | N/A | OK | OK | DONE | — |
| 51 | FM | JO Profit seluruh Job | `ReportController::profitPerJob:56-61` → `FinancialReportService::profitPerJob:78-81` | OK | OK | N/A | `reports.view` | OK | DONE | — |
| 52 | FM | Monthly Profit | `profitMonthly:63-68` / `FinancialReportService::monthlyProfit:83-106`; route `/reports/profit-bulanan` | OK | OK | N/A | `reports.view` | OK | DONE | — |
| 53 | DevOps | Dev/staging/prod env | Hanya `.env` & `.env.local` (dikomit); `.env.example` TIDAK ada | OK | – | – | – | – | **NOT IMPLEMENTED** | Buat `.env.example`; jemaat kredensial dari git |
| 54 | DevOps | CI/CD build/test/deploy | `.github/workflows/tests.yml` (push/pr/nightly, PHP 8.2-8.4) tapi `cp .env.example .env` gagal (file tak ada) → CI rusak; tanpa deploy | – | – | – | – | – | **PARTIAL** | Perbaiki `.env.example`; tambah build/deploy bila diperlukan |
| 55 | DevOps | Web server SSL/domain/RP/env | Tidak ada konfigurasi nginx/RP/SSL/deploy | – | – | – | – | – | **NOT IMPLEMENTED** | Dokumentasikan + sediakan contoh |
| 56 | DevOps | Backup DB + dokumen | Tidak ada (routes/console.php hanya `inspire`; tanpa Schedule) | – | – | – | – | – | **NOT IMPLEMENTED** | Tambah script/schedule backup |
| 57 | DevOps | Monitoring app/server/log/error | Tidak ada setup monitoring | – | – | – | – | – | **NOT IMPLEMENTED** | Tambah telemetry/log pipeline |
| 58 | QA | Test plan/smoke/regression/integration | 25 file, 138 method test (Feature+Unit); cakupan luas (RBAC, workflow, closing, jurnal, SOA, coretax, reimbursement) | – | – | – | – | – | **PARTIAL** | Dokumen test plan belum ada; upload job-dokumen belum ditest |
| 59 | QA | UAT per role & module | Belum ada (tidak ada dokumen/panduan UAT) | – | – | – | – | – | **NOT IMPLEMENTED** | Buat UAT checklist per role |
| 60 | QA | Permission & financial isolation test | Teruji: `WorkspaceTest`, `QuotationTest`, `JobCostTest`, `ClosingPaymentTest`, `DashboardWidgetsTest`, `CoretaxExportTest` | – | – | – | OK | – | DONE | — |
| 61 | Reporting | Deployment/env/backup/monitoring report | Belum ada | – | – | – | – | – | **NOT IMPLEMENTED** | — |
| 62 | Reporting | Sprint progress/risk report | Ada catatan `docs/STAGE_*`, bukan laporan sprint formal | – | – | – | – | – | **NOT IMPLEMENTED** | — |
| 63 | Reporting | QA/UAT & defect closure report | Belum ada | – | – | – | – | – | **NOT IMPLEMENTED** | — |
| 64 | Documentation | API/Architecture/DB docs | `docs/IMPLEMENTATION.md` (STALE: matrix 4 role, "user CRUD belum dibuat", ref `.env.example`), `docs/RBAC_MATRIX.md` (MATCH config), `docs/STAGE_3_4…STAGE_11.md` (per stage) | – | – | – | – | – | **PARTIAL** | Perbaiki IMPLEMENTATION.md; buat ringkasan arsitektur/DB |
| 65 | Documentation | User Guide per role | Belum ada (hanya README) | – | – | – | – | – | **NOT IMPLEMENTED** | Buat guide Sales/CS/Operation/Finance |

---

## Bukti Kunci (file:line)

- RBAC: `config/jobfinance.php:39-67`; `app/Providers/AppServiceProvider.php:24-26`; policies di `app/Policies/*`.
- Activity log: `database/migrations/2026_09_09_000001_enhance_activity_logs.php`; `app/Services/MasterDataService.php:149-162`; `routes/web.php:171`.
- Dashboard: `routes/web.php:165`; `app/Services/DashboardService.php:22-92`; `resources/views/dashboard.blade.php`.
- **B1** `app/Models/CustomerContact.php:10,14`; `app/Http/Controllers/CustomerContactController.php:60-69`; kolom `customer_contacts` (`2026_09_09_000002:29-40`, `2026_09_11_000001:17-22`) tak berisi `lock_version`.
- **B2** `routes/web.php:103` → `app/Http/Controllers/JobController.php` (tanpa `confirmDo`); `resources/views/jobs/show.blade.php:10` memakai `do_confirmed_at` yang tak ada di migrasi.
- **B3** `app/Models/Job.php:20-28` (`statusHistory` & `shipmentStatusHistory` kembar → `JobStatusHistory`); `app/Models/JobShipmentStatus.php` + `2026_09_10_000010` mati.
- **B4** `app/Http/Controllers/JobDocumentController.php` memakai `Storage::disk('private')`; `config/filesystems.php` hanya local/public/s3.
- **B5** `.github/workflows/tests.yml` (cp .env.example); `composer.json:44`; `.env.example` tidak ada.
- **B6** `2026_09_10_000006_add_hierarchy_to_chart_of_accounts.php` vs `2026_09_10_000006_enhance_quotation_header.php`.
- Trade/skema pendukung: `job_status_history` (`2026_09_10_000007:35`), `quotation_status_history` (`2026_09_10_000006:31`), `job_shipment_statuses` (`2026_09_10_000010:18-30`).
- PDF/generate: `app/Http/Controllers/OperationalDocumentController.php:97-149`; view `resources/views/documents/pdf/*`.
- Coretax: `app/Services/CoretaxService.php:17-85`; `app/Http/Controllers/InvoiceController.php:38-50`.
- SOA: `app/Services/StatementOfAccountService.php`; `app/Http/Controllers/StatementOfAccountController.php`.