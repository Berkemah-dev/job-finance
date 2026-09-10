# JobFinance

JobFinance adalah aplikasi demo Job Costing, Billing, dan Accounting untuk perusahaan jasa/logistik. Aplikasi memakai Laravel 12, Blade, Vite, MySQL, dan antarmuka Poppins berwarna putih-biru.

## Modul

- Authentication dan RBAC untuk 8 role (Super Admin s/d Management)
- Dashboard operasional dan keuangan terpisah per role
- Customer, COA, serta mapping akun jurnal
- Quotation, Job Order, biaya Temporary/Provision (dengan referensi quotation), dan status pengiriman
- Closing, snapshot historis, invoice, pembayaran, kurs valas + override kurs, dan kurs terkunci
- Statement of Account (aging, filter belum lunas, pengiriman email), Reimbursement (job/vendor/currency/lampiran)
- Jurnal otomatis, penyesuaian, reversal, dan Buku Besar
- Ekspor faktur PPN ke XML Coretax (pilih faktur, pratinjau, unduh)
- Dashboard, Activity Log, dan administrasi pengguna

## Instalasi lokal

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Sesuaikan koneksi MySQL pada `.env` sebelum migration. Seeder lokal membuat role, permission, COA/mapping, empat akun demo, serta satu alur transaksi bisnis lengkap. Seeder aman dijalankan ulang.

| Role | Email | Cakupan |
| --- | --- | --- |
| Super Admin | `admin@jobfinance.test` | Semua modul dan administrasi |
| Sales Manager | `sales.manager@jobfinance.test` | Quotation (approve), job, pricing |
| Sales | `sales@jobfinance.test` | Quotation, job order |
| Operational | `operational@jobfinance.test` | Customer, quotation, dan Job Order |
| Customer Service | `cs@jobfinance.test` | Job order miliknya, status pengiriman, dokumen |
| Finance | `finance@jobfinance.test` | Biaya, closing, invoice, jurnal, reimbursement, dan laporan |
| Finance Manager | `finance.manager@jobfinance.test` | Seluruh hak Finance ditambah daftar pengguna (users.view) |
| Management | `management@jobfinance.test` | Dashboard dan laporan baca saja |

Password akun demo lokal: `JobFinance!2026`. Cakupan lengkap di [docs/RBAC_MATRIX.md](docs/RBAC_MATRIX.md).

## Verifikasi

```powershell
php artisan test
php vendor/bin/pint --test
php artisan view:cache
npm run build
```

Feature test memakai SQLite in-memory sehingga tidak mengubah database MySQL kerja. Dokumentasi teknis tersedia di [`docs`](docs/IMPLEMENTATION.md); laporan proyek di [`docs/PROJECT_REPORT.md`](docs/PROJECT_REPORT.md).

## Aturan akuntansi utama

Temporary ditagihkan kembali tanpa profit. Provision mengakui modal sebagai HPP dan nilai jual sebagai Pendapatan. Closing dan pembayaran berjalan dalam transaksi database serta menghasilkan jurnal seimbang. Jurnal Posted tidak diedit atau dihapus; koreksi memakai reversal. Akun otomatis berasal dari mapping COA tanpa ID akun hard-code.
