# JobFinance — Tahap 3 dan 4

Tahap berikutnya sudah tersedia: [operasional Job Order dan biaya — Tahap 5](STAGE_5.md). Finance kini memiliki hak baca Job Order untuk pencatatan biaya.

## Fitur yang selesai

### Tahap 3: Master Customer dan COA

- Customer: daftar, pencarian kode/nama/email, pagination, tambah, edit, arsip, dan daftar arsip.
- COA: daftar, pencarian/filter tipe, tambah, edit, arsip; enam tipe akun terkontrol.
- Delapan mapping jurnal: kas, bank, piutang, temporary, provision WIP, pendapatan, HPP, beban.
- Seeder 10 COA awal (8 akun brief ditambah utang usaha dan modal pemilik), tanpa menimpa mapping yang diubah.
- Kode master unik termasuk data arsip. Akun yang masih dipakai mapping tidak dapat diarsipkan atau diganti tipenya.
- Customer menggunakan soft delete; dokumen lama tetap dapat menampilkan customer yang diarsipkan.
- Audit actor dan activity log; pengecekan versi mencegah perubahan dari form lama menimpa data terbaru.

### Tahap 4: Quotation sampai konversi Job Order

- Daftar, pencarian, filter status, pagination, buat/edit Draft, dan halaman detail.
- Item dinamis Temporary/Provision: uraian, satuan, quantity, modal dan nilai jual per unit.
- Preview total dan profit di browser; server menghitung ulang seluruh nominal dan mengabaikan total/status dari request.
- Temporary wajib memiliki modal dan jual yang sama; temporary tidak menambah profit.
- Total temporary, modal/jual provision, subtotal, profit dan margin tersimpan dalam DECIMAL(18,2).
- Alur Draft → Submitted → Approved → Converted; Submitted → Rejected dengan alasan wajib.
- Selain Draft, isi quotation dikunci. Approval memerlukan permission quotations.approve.
- Konversi atomik membuat tepat satu Job Order Draft, nomor unik, snapshot penawaran/customer/items, dan audit.
- Database transaction, row lock, pengecekan versi, dan unique quotation_id pada jobs melindungi konversi ganda.
- Detail/list Job Order dasar sudah tersedia untuk melihat hasil konversi.
- Dashboard menampilkan job terbaru dan jumlah Draft/Open/Closed dari database. Nominal quotation tetap estimasi.

## Akses dan asumsi

| Modul | Super Admin | Operational | Finance | Management |
| --- | --- | --- | --- | --- |
| Customer | Kelola | Kelola | — | — |
| COA dan mapping | Kelola | — | — | — |
| Quotation dan approval | Kelola | Kelola | — | — |
| Konversi dan detail Job Order | Kelola | Kelola | — | — |

- Satu role per user tetap berlaku. Approval Operational mengikuti lingkup quotation dalam brief; approval sendiri diperbolehkan untuk demo. Permission approval dipisahkan agar pembagian tugas dapat disesuaikan.
- Rejected bersifat final pada tahap ini; untuk penawaran baru, buat Draft baru. Tidak ada reopen atau edit dokumen yang telah diajukan.
- Tanggal berlaku disimpan sebagai informasi penawaran, tanpa status expired otomatis atau larangan konversi berdasarkan tanggal. Aturan kedaluwarsa belum ditentukan brief.
- Pembulatan half-up dua desimal per item, kemudian dijumlahkan. Preview memakai BigInt cents; server memakai Brick Math yang sudah tersedia dalam dependency Laravel. Format rupiah tidak melalui floating point.
- Quantity maksimal 999.999,99; nominal unit maksimal 999.999.999,99; maksimal 100 item. Total dicek terhadap batas DECIMAL(18,2).
- Profit negatif diperbolehkan; jika nilai jual provision nol, margin ditampilkan 0,00% agar tidak membagi dengan nol.
- Pajak demo masih nol; nilai yang ditampilkan adalah total sebelum pajak.
- Kode customer/COA diisi pengguna; nomor quotation/job otomatis QUO-YYYYMM-00001 dan JOB-YYYYMM-00001, memakai sequence per bulan.
- Customer aktif wajib tersedia saat menyimpan, mengajukan, menyetujui dan mengonversi. Snapshot customer diperbarui saat menyimpan Draft; sesudah diajukan, identitas historis tidak mengikuti perubahan master.
- Snapshot Job Order merupakan estimasi penawaran, bukan closing snapshot atau biaya aktual.

## Migration dan perubahan database

1. 2026_09_08_000002_create_master_tables: customers, chart_of_accounts, account_mappings.
2. 2026_09_08_000003_create_quotation_tables: document_sequences, quotations, quotation_items.
3. 2026_09_08_000004_rename_queue_jobs_table: rename jobs antrean bawaan menjadi queue_jobs, tanpa penghapusan payload.
4. 2026_09_08_000005_create_business_jobs_table: jobs bisnis dengan foreign key quotation/customer dan snapshot.

Fondasi Job Order dimajukan dari Tahap 5 karena dibutuhkan konversi pada Tahap 4. Pengaturan default antrean sekarang queue_jobs; .env.example memuat DB_QUEUE_TABLE=queue_jobs. Bila menggunakan konfigurasi antrean khusus, pastikan tidak menunjuk tabel jobs bisnis.

Migration dan seeder sudah dijalankan pada MySQL jobfinance. Tidak memakai migrate:fresh atau menghapus transaksi lama. Data browser QA menggunakan SQLite terpisah di storage/app/qa-stage34.sqlite, bukan database kerja.

## File yang dibuat / diperbarui

- Controller: CustomerController, AccountController, QuotationController, JobController; DashboardController.
- Form Request: CustomerRequest, AccountRequest, MappingRequest, QuotationRequest, VersionRequest.
- Model: Customer, ChartOfAccount, AccountMapping, Quotation, QuotationItem, Job.
- Service: MasterDataService, QuotationService, DocumentNumberService, DashboardService.
- Authorization: QuotationPolicy; config/jobfinance.php (permission approval).
- Enum/helper: QuotationStatus, CostType, Support/Money.
- Database: empat migration di atas; ChartOfAccountSeeder, DatabaseSeeder; CustomerFactory, ChartOfAccountFactory, QuotationFactory.
- Konfigurasi: config/accounting.php, config/queue.php, .env.example, routes/web.php, lang/id/validation.php.
- UI: resources/views/customers/*, accounts/*, quotations/*, jobs/*; layouts/app dan dashboard.
- Aset: resources/css/forms.css, resources/css/app.css, resources/js/quotation.js, resources/js/app.js.
- Tests: MasterDataTest dan QuotationTest; test tahap sebelumnya tetap dipertahankan.

## Hasil pengujian

- Tahap 3: 4 feature test / 57 assertion.
- Tahap 4: 11 feature test / 158 assertion.
- Keseluruhan: 25 test / 358 assertion lulus.
- Migration + seed MySQL berhasil; Blade view cache dan build Vite berhasil.
- Browser Edge desktop 1440px/mobile 390px: tambah customer, simpan mapping, tambah/hapus item, sinkronisasi temporary, preview nominal, simpan Draft, submit, approve, convert, detail Job, dashboard.
- Preview dan server sama-sama menghasilkan tagihan Rp9.500.000,00 serta profit Rp1.500.000,00 sesuai contoh brief.
- Tidak ada error JavaScript atau overflow horizontal halaman mobile.
- Ditemukan cache CSS lama pada Vite development; cache diinvalidasi dengan pembaruan timestamp entry CSS, lalu styling formulir dan alur browser diverifikasi ulang.
- Kasus backend meliputi role/URL langsung, validasi, stale updates, status ilegal, customer arsip, presisi/pembulatan, nol/kerugian, total overflow, nomor unik, pemisahan queue, snapshot, konversi ulang, dan rollback job/status/nomor ketika audit gagal.
- Pengujian konkurensi lintas proses MySQL belum dilakukan; perlindungan menggunakan transaction/lock dan constraint unik, dengan test pengiriman ulang dan rollback.

## Batas tahap ini

Belum ada input biaya aktual, perubahan status operasional Job, closing, invoice, pembayaran, atau posting jurnal. Itu mengikuti Tahap 5 dan seterusnya sesuai brief. Tidak ada kendala yang menghalangi penggunaan Tahap 3–4.
