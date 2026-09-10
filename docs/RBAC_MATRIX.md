# Matriks RBAC — JobFinance

Sumber kebenaran permission ada di `config/jobfinance.php` (`permissions` dan `roles`) dan di-sync ke database oleh `RolePermissionSeeder`. Enforcement berlapis:

1. **Route middleware** `can:<permission>` di `routes/web.php`.
2. **Policy** (`app/Policies/*`) dipanggil lewat `Gate::authorize` di service/controller untuk aksi spesifik (contoh: approval quotation, konversi, open/cancel job).
3. **View** `@can` hanya sebagai lapisan UX; backend tetap memutuskan.

Super Admin menerima `*` (seluruh permission) melalui seeder.

## Permission

| Permission | Arti |
| --- | --- |
| `dashboard.view` | Membuka dashboard |
| `customers.view` / `customers.manage` | Lihat / kelola customer |
| `coa.manage` | Kelola Chart of Accounts dan mapping jurnal |
| `quotations.manage` | Kelola quotation (buat/edit/submit) |
| `quotations.approve` | Approve / reject / minta revisi quotation |
| `jobs.view` | Lihat Job Order dan dokumen |
| `jobs.manage` | Edit operasional, open, cancel job |
| `costs.manage` | Kelola biaya job |
| `jobs.close` | Closing job, invoice, pembayaran |
| `invoices.manage` | Invoice + ekspor Coretax |
| `payments.manage` | Catat pembayaran customer |
| `journals.manage` | Jurnal dan reversal |
| `reimbursements.manage` | Reimbursement |
| `reports.view` | Laporan keuangan (GL, neraca, laba rugi, dll) |
| `financial.view` | Melihat data finansial sensitif (modal, profit, biaya) |
| `vendors.manage` | Kelola vendor |
| `pricing.manage` / `pricing.view` | Kelola / lihat pricing (weekly rate, trucking) |
| `email.manage` | Kirim komunikasi ke customer (SOA email) |
| `users.view` / `users.manage` | Lihat / kelola pengguna |
| `roles.manage`, `settings.manage` | Didefinisikan, hanya super-admin via `*` (belum dipakai) |
| `activity.view` | Halaman Audit Trail |

## Matriks Role × Permission

| Permission | Super Admin | Finance | Finance Manager | Sales Manager | Sales | Operation | Customer Service | Management |
| --- | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: |
| dashboard.view | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| customers.view | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| customers.manage | ✔ | — | — | ✔ | ✔ | ✔ | — | — |
| coa.manage | ✔ | — | — | — | — | — | — | — |
| quotations.manage | ✔ | — | — | ✔ | ✔ | — | — | — |
| quotations.approve | ✔ | — | — | ✔ | — | — | — | — |
| jobs.view | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| jobs.manage | ✔ | — | — | ✔ | — | ✔ | ✔ | — |
| costs.manage | ✔ | ✔ | ✔ | — | — | — | — | — |
| jobs.close | ✔ | ✔ | ✔ | — | — | — | — | — |
| invoices.manage | ✔ | ✔ | ✔ | — | — | — | — | — |
| payments.manage | ✔ | ✔ | ✔ | — | — | — | — | — |
| journals.manage | ✔ | ✔ | ✔ | — | — | — | — | — |
| reimbursements.manage | ✔ | ✔ | ✔ | — | — | — | — | — |
| reports.view | ✔ | ✔ | ✔ | — | — | — | — | — |
| financial.view | ✔ | ✔ | ✔ | — | — | — | — | — |
| vendors.manage | ✔ | — | — | ✔ | — | ✔ | — | — |
| pricing.manage | ✔ | — | — | ✔ | — | — | — | — |
| pricing.view | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | — |
| email.manage | ✔ | ✔ | ✔ | — | — | — | — | — |
| users.view | ✔ | — | ✔ | — | — | — | — | — |
| users.manage | ✔ | — | — | — | — | — | — | — |
| roles.manage | ✔ | — | — | — | — | — | — | — |
| settings.manage | ✔ | — | — | — | — | — | — | — |
| activity.view | ✔ | ✔ | ✔ | — | — | — | — | — |

## Aturan domain penting

- **Data finansial sensitif** (`financial.view`) hanya dimiliki Finance, Finance Manager, dan Super Admin. Role lain tidak menerima field finansial dari backend: controller men-strip modal/profit dari payload sebelum dirender (`JobController::show`, `QuotationController::show`), dashboard mengosongkan kalkulasi finansial bila tanpa permission, dan route laporan dibungkus `can:reports.view`.
- **Approval quotation** hanya Sales Manager dan Super Admin (`quotations.approve`). Route `/quotations/{id}/approve|reject|revise` diproteksi `can:quotations.approve` plus policy status `submitted`.
- **Konversi quotation → job** memerlukan `quotations.manage` + `jobs.manage`; policy memastikan status `approved` dan hanya sekali.
- **Closing/invoice/pembayaran/jurnal/reimbursement** terkunci `jobs.close` / `invoices.manage` / `payments.manage` / `journals.manage` / `reimbursements.manage` (Finance dan Finance Manager).
- **Pengelolaan pengguna & role** hanya Super Admin. Finance Manager mendapat `users.view` (read-only daftar pengguna) sehingga membedakan peran eksekutor (Finance) dari supervisor keuangan (Finance Manager).

## Catatan teknis

- Finance adalah eksekutor operasional harian (closing, invoice, pembayaran, jurnal, reimbursement) tanpa akses daftar pengguna; Finance Manager adalah supervisor yang mewarisi seluruh permission Finance plus `users.view` (read-only daftar pengguna).
- `roles.manage` dan `settings.manage` didefinisikan tetapi belum memiliki endpoint; hanya aktif lewat super-admin `*`.
- Role `management` hanya melihat dashboard; guard: tak ada permission lain.