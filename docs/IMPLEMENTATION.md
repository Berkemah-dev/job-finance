# JobFinance — Tahap 1 dan 2

Catatan ini mendokumentasikan fondasi awal. Implementasi terbaru Tahap 3–4, perubahan migration Job Order, aturan quotation, dan hasil QA tersedia di [STAGE_3_4.md](STAGE_3_4.md).

## Pemeriksaan awal

- Repository Laravel dasar; belum ada modul bisnis atau autentikasi.
- Laravel terpasang 12.69.1, Composer 2.9.2, Node 24.18.0, npm 11.16.0.
- Tailwind CSS 4, Vite 6, Blade, MySQL lokal database jobfinance.
- PHP terminal mengarah ke XAMPP 8.2.12, sementara dependency terpasang membutuhkan >=8.4.1. Gunakan PHP Laragon 8.4.25 untuk CLI dan web server.
- Migration bawaan users, cache, dan queue sudah berjalan sebelum perubahan.
- File brief dan package-lock.json sudah untracked saat pemeriksaan; keduanya dipertahankan.
- Tidak ditemukan AGENTS.md yang berlaku.

## Rancangan database dan relasi

| Tabel | Kolom/relasi inti |
| --- | --- |
| users | role_id → roles; nama, email unik, password hash |
| roles / permissions | name unik, pivot permission_role dengan primary key gabungan |
| customers | kode unik, identitas, alamat, kontak, soft delete |
| chart_of_accounts | kode unik, nama, tipe, parent_id opsional, soft delete |
| account_mappings | key unik → COA; cash, bank, receivable, temporary, provision_wip, revenue, cogs, expense |
| quotations | nomor unik, customer_id, tanggal, status, total |
| quotation_items | quotation_id, uraian, jenis, quantity, modal, nilai jual |
| jobs | nomor unik, customer_id, quotation_id, status, tanggal operasional |
| job_costs | job_id, jenis temporary/provision, modal, nilai jual, status final, tanggal |
| job_closing_snapshots | job_id unik, tanggal, closed_by, total temporary/modal/jual, profit, margin, salinan detail |
| invoices / invoice_items | nomor unik, job_id, customer_id, snapshot referensi, jatuh tempo, status; detail tagihan |
| payments | nomor unik, invoice_id, jumlah, tanggal, akun penerimaan |
| journals / journal_entries | nomor unik, tanggal, sumber, status, posted_by; journal_id, coa_id, debit, kredit |
| activity_logs | user_id, action, description, timestamps; metadata sumber transaksi ditambahkan saat modul bisnis |
| document_sequences | jenis/periode unik, counter; dikunci saat penomoran transaksi |

Seluruh nominal bisnis menggunakan DECIMAL(18,2), perhitungan desimal presisi, foreign key, index tanggal/status, serta audit actor dan timestamp relevan. Nilai closing dan detail invoice disalin untuk menjaga histori. Tidak menggunakan hard-coded ID akun.

## Urutan migration

1. Tahap 2: create_access_control_tables (roles, permissions, pivot, users.role_id, activity_logs).
2. Tahap 3: create_customers, create_chart_of_accounts, create_account_mappings.
3. Tahap 4: create_document_sequences, create_quotations, create_quotation_items.
4. Tahap 5: rename_queue_jobs_table (jobs bawaan Laravel → queue_jobs, ubah config antrean), create_jobs, create_job_costs.
5. Tahap 6: create_job_closing_snapshots, create_invoices, create_invoice_items, create_journals, create_journal_entries.
6. Tahap 7: create_payments.
7. Tahap 8–10: index/metadata tambahan hanya jika dibutuhkan laporan dan audit.

Migration yang sudah diterapkan tidak ditulis ulang. Rename antrean menggunakan migration baru agar payload lama tetap terjaga.

## Matriks akses

| Area | Super Admin | Operational | Finance | Management |
| --- | --- | --- | --- | --- |
| Dashboard | Lihat | Lihat operasional | Lihat | Lihat |
| Customer, quotation, job | Kelola | Kelola | — | — |
| COA, mapping akun | Kelola | — | — | — |
| Biaya, closing, invoice, pembayaran | Kelola | — | Kelola | — |
| Jurnal | Kelola | — | Kelola | — |
| Laporan, profit per job | Lihat | — | Lihat | Lihat |
| User, role, pengaturan | Kelola | — | — | — |
| Activity log | Lihat | — | — | — |

Saat ini direktori pengguna dan role bersifat baca saja. CRUD user/role/pengaturan belum dibuat. Permission modul masa depan sudah disiapkan, tetapi endpoint bisnis belum tersedia. RBAC menggunakan tabel relasional, Gate, middleware can, dan UserPolicy; role_id tidak mass assignable. User tanpa role ditolak.

## Rancangan jurnal otomatis

Asumsi awal pengeluaran dibayar langsung melalui kas/bank, tanpa utang vendor.

| Peristiwa | Debit | Kredit |
| --- | --- | --- |
| Pengeluaran temporary | Temporary Job | Kas/Bank |
| Pengeluaran provision | Provision / WIP | Kas/Bank |
| Closing penagihan | Piutang = temporary + nilai jual | Temporary + Pendapatan sebesar nilai jual provision |
| Closing modal | HPP sebesar modal provision | Provision / WIP |
| Pembayaran customer | Kas/Bank | Piutang |
| Beban operasional | Beban Operasional | Kas/Bank |

Contoh brief: closing debit Piutang Rp9.500.000 dan HPP Rp3.000.000; kredit Temporary Rp5.000.000, Pendapatan Rp4.500.000, WIP Rp3.000.000. Debit = kredit Rp12.500.000; profit Rp1.500.000.

Jurnal di atas merupakan rancangan demo, belum diimplementasikan. Konfirmasi Finance pada tahap closing mencakup pajak, utang vendor, pembatalan, reopen, adjustment/reversal, tanggal posting, dan pembulatan. Closing wajib transaction dan lock untuk mencegah invoice/jurnal ganda; pembayaran wajib lock saldo agar tidak overpayment. Posted tidak dapat diubah langsung.

## Tahapan dan asumsi

Tahap 1–2: pemeriksaan, login/logout, RBAC, dashboard, navigasi, akun demo, log autentikasi, pengujian.
Tahap 3: customer dan COA. Tahap 4: quotation. Tahap 5: job dan biaya. Tahap 6: closing/invoice/jurnal. Tahap 7: pembayaran. Tahap 8: jurnal/GL/trial balance. Tahap 9: laporan/dashboard berbasis transaksi. Tahap 10: QA dan dokumentasi akhir.

- Satu role per user; tanpa registrasi publik.
- UI berbahasa Indonesia, Poppins lokal, putih-biru, responsif.
- Zona waktu default Asia/Jakarta (dapat diubah dengan APP_TIMEZONE), IDR, pajak awal nol.
- Ringkasan dashboard nol sebagai kondisi workspace awal, bukan hasil agregasi transaksi. Grafik dan angka nyata di Tahap 9.
- Menu yang belum tersedia diberi label Segera dan tidak berupa tautan.
- Akun demo hanya dibuat pada environment local/testing; seeding ulang tidak mereset password atau menggandakan user.
- Tidak ada transaksi dummy, migration fresh, atau penghapusan data lama.

## Menjalankan secara lokal (PowerShell)

Pastikan web server memakai PHP 8.4.25 dan document root menunjuk public.

```powershell
$jobFinancePhp = 'C:\laragon\bin\php\php-8.4.25-nts-Win32-vs17-x64\php.exe'
& $jobFinancePhp artisan migrate --seed
npm install
npm run build
& $jobFinancePhp artisan serve --host=127.0.0.1 --port=8000
```

Buka http://127.0.0.1:8000/login. Konfigurasi MySQL ada di .env; contoh ada di .env.example.

| Akun | Email |
| --- | --- |
| Super Admin | admin@jobfinance.test |
| Operational | operational@jobfinance.test |
| Finance | finance@jobfinance.test |
| Management | management@jobfinance.test |

Password demo lokal: `JobFinance!2026`.

## Verifikasi

```powershell
& $jobFinancePhp artisan test
& $jobFinancePhp vendor/bin/pint --dirty --test
npm run build
```

Feature test memakai SQLite in-memory terisolasi, sehingga tidak mengubah database MySQL kerja. Migration dan seed juga dijalankan pada MySQL lokal. Pengujian meliputi guest protection, validation, login/logout dan audit, throttle, matriks permission, akses URL langsung, role kosong, dan seeding berulang.

Hasil: 10 test lulus dengan 139 assertion; build Vite dan kompilasi Blade berhasil. Browser Edge headless memverifikasi login, toggle password, dashboard desktop 1440px dan mobile 390px, navigasi sidebar, direktori pengguna, dan logout. Poppins termuat secara lokal dan tidak ada error JavaScript.

## File implementasi

- Backend: app/Http/Controllers/{AuthController,DashboardController,AccessController}.php; app/Http/Requests/LoginRequest.php; app/Services/AuthenticationService.php; app/Policies/UserPolicy.php.
- Model/akses: app/Models/{User,Role,Permission,ActivityLog}.php; app/Providers/AppServiceProvider.php; config/jobfinance.php; routes/web.php.
- Database: migration create_access_control_tables; DatabaseSeeder, RolePermissionSeeder, DemoUserSeeder.
- UI: resources/views/layouts/app.blade.php, auth/login.blade.php, dashboard.blade.php, access/{users,activity}.blade.php, components/icon.blade.php; resources/css/{app,jobfinance}.css; resources/js/app.js.
- Konfigurasi: config/app.php, .env.example, .env lokal (nama aplikasi/locale), package.json/package-lock.json, .gitignore.
- Pengujian: phpunit.xml, tests/Feature/{ExampleTest,WorkspaceTest}.php.
