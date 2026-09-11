# REMOVED_OR_MERGED — JobFinance

Status tahap audit: **belum ada perintah eksekusi** — seluruh item di bawah adalah kandidat yang sudah dilakukan pengecekan dependency (route/import/consumer/FK) dan menunggu persetujuan sebelum dieksekusi. Tidak ada hapus/gabung yang sudah berjalan.

## A. Kandidat MERGE (hapus duplikasi)

| Item | File | Alasan | Status Datenan | Kebutuhan Migrasi Data |
| ---- | ---- | ------ | -------------- | ---------------------- |
| Alias route `job-orders.*` → `jobs.*` | `routes/web.php:97-99`; menu/link pakai `job-orders.index/show/edit` | Duplikat URL untuk fungsi yang sama (`JobController`). Pakai `jobs.*` sebagai kanonik. | Tidak ada — hanya rute/view/link | Tidak ada |
| Redirect `/documents` | `routes/web.php:70` | Path dwibahasa; kanonik `/dokumen-job`. | Tidak ada | Tidak ada |
| Tabel `job_shipment_statuses` + model `JobShipmentStatus` | `2026_09_10_000010`; `app/Models/JobShipmentStatus.php` | Dead code — status shipment faktanya tertulis ke `job_status_history`. Tabel kosong (tidak pernah dipakai). Pertahankan `job_status_history` sbg satu riwayat (data existing tak bisa dipisah tanpa diskriminator). | `Job.php:25-27` diperbaiki agar tidak menyiratkan tabel kedaluwarsa; `JobService::updateShipmentStatus` tetap menulis `job_status_history` | Tidak ada (tabel kosong; drop-safe via migrasi) |
| `resources/views/welcome.blade.php` | `resources/views/welcome.blade.php` | Stub Laravel; `/` redirect ke `/dashboard`, `view('welcome')` tak dipakai. | Tidak ada | Tidak ada |
| Permission tak terpakai `roles.manage`, `settings.manage` | `config/jobfinance.php:27-28` | Didefinisikan tapi tidak ada gate/policy/route yang memakainya (RBAC). | Tidak ada | Tidak ada (hapus dari config + seeder idempotent) |
| Nomor migrasi ganda `2026_09_10_000006` | `add_hierarchy_to_chart_of_accounts.php` vs `enhance_quotation_header.php` | Dua file bernomor sama → sorting kuratif; migrasi ini WAJIB berjalan berurutan (COA hierarchy sebelum quotation?). Rename file agar urutan deterministik. | Tidak ada (renama file) | Tidak ada |

## B. Kandidat FIX (bukan hapus) — lihat juga `AUDIT_FEATURES.md`

- **B1** tambah kolom `lock_version` di `customer_contacts` (model+controller sudah memakainya).
- **B2** implementasi DO Selesai (method `JobController::confirmDo` + service + kolom `jobs.do_confirmed_at/by`).
- **B3** arahkan `Job::shipmentStatusHistory()` konsisten dengan penyimpanan nyata (lihat keputusan MERGE A3 di atas).
- **B4** konfigurasi disk penyimpanan dokumen job (ganti `private` → disk yang ada / tambah `private`) + metadata.
- **B5** buat `.env.example`; pindahkan `.env`, `.env.local` keluar dari git (pertahankan nilai lokal).
- **B6** nomor migrasi ganda (lihat A).
- **B7** binding SOA `{customer}` → gunakan `Customer::withTrashed()` agar customer diarsipkan tetap tampil di SOA.

## C. Prinsip yang dijaga

1. **Tidak ada data bisnis yang dihapus/di-drop**: customer, quotation, job, cost, invoice, payment, journal, reimbursement, attachment — aman.
2. Setiap perubahan DB = migration aman (add column / drop tabel kosong), bukan drop tabel isi.
3. Tidak ada module baru untuk field kecil — konsolidasi ke modul inti, perbedaan via role/permission/status/category/template.