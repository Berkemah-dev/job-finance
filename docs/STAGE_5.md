# JobFinance — Tahap 5

## Fitur yang selesai

- Data operasional Job Order: nama pekerjaan, tanggal job, target selesai, jenis layanan, asal/tujuan, referensi pengiriman, deskripsi muatan, dan catatan.
- Workflow Draft → Open; Open → Cancelled dengan alasan wajib. Closed tetap dicadangkan untuk proses closing Tahap 6.
- Job tetap berasal dari konversi quotation. Identitas customer, nomor job, quotation asal, dan snapshot penawaran tidak dapat diganti melalui formulir operasional.
- Finance dapat melihat Job Order dengan permission jobs.view, tanpa hak mengubah data operasional.
- Menu Biaya Job, daftar biaya per job, filter jenis/status, pagination, halaman tambah/edit/detail.
- Biaya Temporary dan Provision disimpan sebagai Draft; finalisasi mengunci biaya menjadi Final.
- Penghapusan hanya untuk Draft pada job Open, menggunakan soft delete. Biaya yang dihapus dikeluarkan dari total; actor dan audit tetap tersimpan.
- Quantity, modal, jual, dan total memakai DECIMAL(18,2). Total dihitung ulang di server; preview browser memakai BigInt cents.
- Ringkasan Temporary, modal/jual Provision, estimasi tagihan, profit dan margin, terpisah untuk Draft, Final dan keseluruhan.
- Dashboard menampilkan jumlah job aktual serta Temporary/Provision Final pada job Open. Pendapatan dan HPP belum diakui sebelum closing.

## Aturan bisnis dan asumsi

| Tindakan | Super Admin | Operational | Finance | Management |
| --- | --- | --- | --- | --- |
| Lihat job | Ya | Ya | Ya | Tidak; ringkasan dashboard saja |
| Edit operasional, buka, batalkan | Ya | Ya | Tidak | Tidak |
| Lihat / catat / edit / hapus Draft biaya | Ya | Tidak | Ya | Tidak |
| Finalisasi biaya | Ya | Tidak | Ya | Tidak |

1. Operasional dapat diedit saat Draft atau Open. Tanggal job dikunci setelah Open agar histori biaya tetap konsisten.
2. Membuka job membutuhkan customer aktif dan tanggal job yang tidak melewati hari ini.
3. Customer yang diarsipkan setelah job Open tidak menghentikan pencatatan biaya pekerjaan yang sudah berjalan; identitas historis tetap berasal dari snapshot.
4. Pembatalan hanya dari Open, dengan alasan dan tanpa biaya aktif. Draft biaya perlu dihapus dahulu. Job yang sudah memiliki biaya Final tidak bisa dibatalkan melalui alur ini.
5. Biaya baru/edit/finalisasi/hapus hanya pada job Open. Semua mutasi ditolak untuk job Draft, Closed atau Cancelled.
6. Tanggal biaya antara tanggal job dan hari ini. Quantity minimal 0,01 dan maksimal 999.999,99; nominal per unit maksimal 999.999.999,99.
7. Temporary wajib memiliki modal dan jual yang sama. UI menyalin nilai jual dari modal; server tetap memvalidasi aturan ini.
8. Provision boleh memiliki profit negatif atau jual nol; margin nol saat pembaginya nol. Pembulatan half-up dua desimal per baris.
9. Biaya aktual tidak otomatis diambil dari item quotation. Snapshot penawaran tetap estimasi dan tidak berubah ketika biaya aktual diinput.
10. Finalisasi berarti biaya telah diperiksa dan dikunci. Tahap ini belum membuat jurnal, invoice, pencatatan kas/bank, atau pembayaran.
11. Koreksi biaya Final belum tersedia pada tahap ini; perubahan langsung dan penghapusan ditolak. Adjustment/reversal dan integrasi jurnal ditangani pada tahap akuntansi berikutnya.
12. Nomor biaya otomatis memakai CST-YYYYMM-00001, unik dan mengikuti sequence bulanan.

## Integritas dan otorisasi

- Middleware permission, JobPolicy, JobCostPolicy, dan Form Request melindungi URL langsung maupun tombol UI.
- Nested scoped route binding memastikan biaya hanya bisa diakses melalui job pemiliknya.
- Setiap mutasi memakai DB::transaction() dengan row lock job lebih dahulu, kemudian biaya.
- Versi job dan biaya disertakan dalam form. Form lama atau pengiriman ulang pembuatan biaya ditolak jika versi job sudah berubah.
- Semua mutasi biaya menaikkan versi job, sehingga pembatalan/operasional dari halaman lama juga harus memuat ulang data.
- Status, actor, nomor dokumen, total, dan metadata finalisasi ditentukan server; input tambahan dari browser diabaikan.
- Total agregat divalidasi terhadap batas DECIMAL(18,2), termasuk saat menyimpan biaya tambahan.
- Gagal audit menyebabkan transaksi, perubahan status/versi, dan sequence nomor ikut rollback.
- Histori aktivitas mencakup job.updated/open/cancel dan job_cost.created/updated/deleted/finalized.

## Migration dan file

Migration baru: 2026_09_08_000006_add_job_operations_and_costs. Menambahkan kolom operasional/audit/versi pada jobs dan tabel job_costs beserta foreign key, index, timestamps, dan soft delete.

File utama:

- app/Services/JobService.php, JobCostService.php; DashboardService diperbarui.
- app/Models/JobCost.php; Job diperluas dengan relationship costs dan cast tanggal.
- app/Policies/JobPolicy.php, JobCostPolicy.php.
- app/Http/Requests/JobRequest.php, JobCostRequest.php, CostVersionRequest.php.
- app/Http/Controllers/JobCostController.php; JobController diperluas.
- config/operations.php, config/jobfinance.php; routes/web.php, lang/id/validation.php.
- resources/views/jobs/{index,show,form}.blade.php, resources/views/costs/*, layouts/app.blade.php, dashboard.blade.php.
- resources/js/job-cost.js dan app.js; resources/css/operations.css dan app.css.
- database/factories/JobFactory.php, JobCostFactory.php.
- tests/Feature/JobOperationsTest.php, JobCostTest.php; QuotationTest menyesuaikan hak baca job Finance.

RolePermissionSeeder yang sudah ada menggunakan konfigurasi permission terbaru. Migration dan seed sudah dijalankan pada MySQL jobfinance tanpa menghapus atau membuat ulang data kerja.

## Pengujian

- 19 test khusus Tahap 5 dengan 229 assertion.
- Keseluruhan 44 test dengan 591 assertion lulus, termasuk regresi Tahap 1–4.
- Cakupan: workflow/role, customer arsip, tanggal, snapshot, perubahan dari form lama, biaya lintas job, angka desimal, markup Temporary, profit negatif, nol, total overflow, pengiriman ulang, Final terkunci, soft delete, dashboard, dan rollback audit.
- Build Vite berhasil. PHP 8.4.25 Laragon digunakan sesuai dependency project.
- Pemeriksaan Pint, git diff --check, kompilasi Blade dan status migration berhasil.
- Browser Edge desktop 1440px/mobile 390px lulus: Operational edit/buka job; Finance tambah Temporary/Provision, edit Draft, finalisasi, hapus Draft, dan verifikasi dashboard.
- Preview serta hasil tersimpan sesuai contoh brief: Temporary Rp5.000.000, modal Provision Rp3.000.000, jual Provision Rp4.500.000, potensi tagihan Rp9.500.000, estimasi profit Rp1.500.000.
- Screenshot diperiksa; tidak ada error JavaScript maupun overflow horizontal halaman mobile. Server QA dihentikan setelah pengujian.
- Feature test menggunakan SQLite in-memory. Database QA browser terpisah di storage/app/qa-stage5.sqlite; data contoh tidak masuk MySQL kerja.
- Row lock dan constraint digunakan untuk transaksi bersamaan; load test konkurensi lintas proses MySQL belum dilakukan.

## Cara menggunakan

1. Login sebagai Operational atau Super Admin.
2. Buka Job Order hasil konversi quotation → Edit operasional → Simpan → Buka job.
3. Login sebagai Finance atau Super Admin → Biaya Job → pilih job → Tambah biaya.
4. Isi Temporary/Provision, tanggal, quantity, modal/jual, dan referensi → Simpan biaya Draft.
5. Periksa halaman detail. Edit atau hapus jika masih Draft; pilih Finalisasi biaya setelah benar.
6. Pantau biaya Draft dan Final pada ringkasan job. Closing dilanjutkan pada Tahap 6.

Akun dan password demo tetap mengikuti dokumentasi fondasi. Finance: finance@jobfinance.test; Operational: operational@jobfinance.test.
