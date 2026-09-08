# JobFinance

JobFinance adalah aplikasi demo Job Costing, Billing, dan Accounting untuk perusahaan jasa/logistik. Aplikasi memakai Laravel 12, Blade, Vite, MySQL, dan antarmuka Poppins berwarna putih-biru.

## Modul

- Authentication dan RBAC untuk Super Admin, Operational, Finance, dan Management
- Customer, COA, serta mapping akun jurnal
- Quotation, Job Order, dan biaya Temporary/Provision
- Closing, snapshot historis, invoice, serta pembayaran
- Jurnal otomatis, penyesuaian, dan reversal
- Buku Besar, Neraca Saldo, Neraca, Laba Rugi, Arus Kas, dan Profit per Job
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
| Operational | `operational@jobfinance.test` | Customer, quotation, dan Job Order |
| Finance | `finance@jobfinance.test` | Biaya, closing, invoice, jurnal, dan laporan |
| Management | `management@jobfinance.test` | Dashboard dan laporan baca saja |

Password akun demo lokal: `JobFinance!2026`.

## Verifikasi

```powershell
php artisan test
php vendor/bin/pint --test
php artisan view:cache
npm run build
```

Feature test memakai SQLite in-memory sehingga tidak mengubah database MySQL kerja. Dokumentasi teknis tersedia di [`docs`](docs/IMPLEMENTATION.md).

## Aturan akuntansi utama

Temporary ditagihkan kembali tanpa profit. Provision mengakui modal sebagai HPP dan nilai jual sebagai Pendapatan. Closing dan pembayaran berjalan dalam transaksi database serta menghasilkan jurnal seimbang. Jurnal Posted tidak diedit atau dihapus; koreksi memakai reversal. Akun otomatis berasal dari mapping COA tanpa ID akun hard-code.
