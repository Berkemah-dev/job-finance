# UAT CHECKLIST — JOBFINANCE
User Acceptance Testing Matrix per Role & Modul

---

## 1. Role: Super Admin & Administrasi

| No | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| 1.1 | Login & Session Protection | Login dengan kredensial valid / salah | Login sukses masuk dashboard; salah password menampilkan notifikasi error; proteksi throttling | ✅ Pass |
| 1.2 | Manajemen Pengguna & Role | Tambah user baru, assign role, edit data | User tersimpan, role terasosiasi, proteksi penghapusan super-admin terakhir | ✅ Pass |
| 1.3 | Log Aktivitas Global | Buka menu Log Aktivitas | Semua aktivitas (create, update, delete, approval, closing, dll) tercatat dengan timestamp, IP, dan actor | ✅ Pass |
| 1.4 | Ganti Tema Gelap / Terang | Klik tombol tema di topbar | Tampilan beralih antara Dark Mode & Light Mode secara mulus | ✅ Pass |

---

## 2. Role: Sales & Sales Manager

| No | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| 2.1 | Registrasi Customer & Upload | Tambah customer baru + upload NPWP & NIB | Kode CUS-YYYY-NNNNN digenerate otomatis; file dokumen tersimpan | ✅ Pass |
| 2.2 | Tambah Kontak Shipper / Consignee | Tambah kontak di detail customer | Kontak tersimpan dengan nomor telepon, email, dan alamat pengiriman | ✅ Pass |
| 2.3 | Buat Quotation Penawaran | Isi rincian penawaran, item biaya, dan Incoterms | Quotation tersimpan sebagai Draft, grand total & estimasi profit terhitung | ✅ Pass |
| 2.4 | Quick Trucking Tariff | Ambil tarif trucking di form quote | Nilai modal & harga jual terisi otomatis dari master trucking | ✅ Pass |
| 2.5 | Submit & Approval Flow | Sales submit -> Sales Manager approve/revise/reject | Status berubah sesuai aksi, notifikasi status terkirim | ✅ Pass |
| 2.6 | Konversi Quotation ke Job Order | Klik tombol Convert pada quotation approved | Job Order baru terbentuk otomatis membawa seluruh snapshot data penawaran | ✅ Pass |
| 2.7 | Kalkulator Pajak, LCL, & Volume | Hitung simulasi di menu Kalkulator | Hasil kalkulasi Bea Masuk, PPN, PPh 22, CBM, dan Vol Weight akurat | ✅ Pass |

---

## 3. Role: Customer Service (CS)

| No | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| 3.1 | Monitoring Job Order | Buka daftar Job Order | Format tabel menampilkan No Job, Customer, BL/AWB, Layanan, Quote/Sales, CS PIC | ✅ Pass |
| 3.2 | Detail Job Order | Buka detail job order | Informasi Shipper, Consignee, POL, POD, ETD, ETA, BL/AWB, Vessel/Flight, Qty, Gross Weight, Volume, Commodity tampil lengkap | ✅ Pass |
| 3.3 | Booking Confirmation & SI | Buat Booking Confirmation & Shipping Instruction | Dokumen tersimpan dan dapat dicetak/preview format PDF resmi | ✅ Pass |
| 3.4 | Upload Dokumen Job | Unggah BL, CI, Packing List di tab dokumen | Dokumen tersimpan aman dan dapat diunduh | ✅ Pass |
| 3.5 | Konfirmasi DO Selesai | Klik "Konfirmasi DO Selesai" pada job aktif | Timestamp dan PIC konfirmasi DO tercatat; tombol terkunci | ✅ Pass |
| 3.6 | Isolasi Keuangan | Buka detail job order dengan akun CS | Ringkasan profit dan modal aktual tidak terlihat oleh CS | ✅ Pass |

---

## 4. Role: Operation

| No | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| 4.1 | Master TPS Air & Sea | Tambah/Edit master TPS moda Air dan Sea | Data tersimpan dengan format Kota – Nama TPS – Kode TPS | ✅ Pass |
| 4.2 | Update Status Pengiriman | Update status ke SPJM / SPPB / Arrived | Status pengiriman terupdate dan riwayat timeline tercatat | ✅ Pass |
| 4.3 | Cetak DNP & SK Kepabeanan | Unduh PDF DNP dan SK Kepabeanan di tab dokumen | PDF tergenerate rapi dengan data dinamis job order | ✅ Pass |
| 4.4 | Cetak Surat Jalan & Tanda Terima | Unduh Surat Jalan & Tanda Terima Barang | Dokumen operasional pengiriman tergenerate dengan lengkap | ✅ Pass |

---

## 5. Role: Finance & Finance Manager

| No | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| 5.1 | Pencatatan Biaya Aktual | Buka Biaya Job > Buat Biaya Draft & Finalisasi | Charges & Reimbursement tercatat dengan referensi vendor & bukti | ✅ Pass |
| 5.2 | Closing Job Order | Lakukan Closing Job dengan kurs invoice | Invoice komersial terbit otomatis; jurnal piutang tercatat | ✅ Pass |
| 5.3 | Cetak PDF Invoice | Klik Preview / Cetak PDF Invoice | PDF invoice komersial memuat kop resmi, rincian charges/reimbursement, PPN, terbilang, dan rekening bank BCA/Mandiri | ✅ Pass |
| 5.4 | Status Pengiriman Invoice Fisik | Update status pengiriman invoice fisik (not_sent, sent, received) | Nomor resi, tanggal kirim, dan tanggal terima terupdate pada invoice | ✅ Pass |
| 5.5 | Ekspor Coretax XML | Pilih invoice ber-PPN > Ekspor Coretax XML | File XML terbentuk sesuai skema validasi DJP Coretax | ✅ Pass |
| 5.6 | Statement of Account & Email Log | Buka SOA customer > Kirim via email | Email terkirim ke customer; log pengiriman tercatat di tabel riwayat email | ✅ Pass |
| 5.7 | Jurnal & Laporan Keuangan | Periksa Neraca, Laba Rugi, Buku Besar | Jurnal seimbang (Debit = Kredit); laporan keuangan realtime | ✅ Pass |
| 5.8 | JO Profit & Profit Bulanan (FM) | Akses laporan profitabilitas | Menampilkan margin keuntungan per job dan tren laba rugi bulanan | ✅ Pass |
