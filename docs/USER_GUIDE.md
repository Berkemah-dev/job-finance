# PANDUAN PENGGUNA (USER GUIDE) — JOBFINANCE
Aplikasi Manajemen Operasional & Finansial Freight Forwarder / Logistik

---

## 👥 1. Role & Hak Akses Pengguna

Aplikasi **JobFinance** memiliki 7 peran (role) terintegrasi dengan pemisahan tugas (*separation of duties*):

| Role | Tanggung Jawab Utama |
|---|---|
| **Super Admin** | Akses penuh ke seluruh menu, konfigurasi sistem, user management, dan audit log. |
| **Sales** | Membuat Quotation penawaran harga, simulasi kalkulator pajak & volume/CBM, memantau pricing mingguan. |
| **Sales Manager** | Membuat dan menyetujui (Approve/Reject/Revise) Quotation dari Sales, mengelola master pricing trucking. |
| **Customer Service** | Memonitor Job Order, membuat Booking Confirmation & Shipping Instruction, upload dokumen operasional (BL/AWB, Packing List, Invoice), konfirmasi DO Selesai. |
| **Operation** | Mengelola master TPS (Air & Sea), update milestone pengiriman (SPJM, SPPB, dll), upload dokumen job lapangan, cetak DNP & SK Kepabeanan. |
| **Finance** | Mencatat biaya aktual job (Charges/Reimbursement), closing job & penerbitan Invoice, penerimaan pembayaran (Payment), cetak & kirim SOA, ekspor Coretax XML, pembukuan jurnal & neraca. |
| **Finance Manager** | Akses penuh seluruh modul Finance, laporan profitabilitas per job (JO Profit), dan analisis laba rugi bulanan. |

---

## 📊 2. Panduan Dashboard per Role

1. **Super Admin**: Menampilkan metrik menyeluruh (Total Job, Pipeline Quotation, Aging Invoice, Snapshot Laba Rugi).
2. **Finance**: Widget jatuh tempo invoice (*Invoice Due Date*), status piutang, dan kurs konversi mingguan.
3. **Finance Manager**: Widget *Job Profit*, tren pendapatan/keuntungan bulanan, dan kurs valuta asing.
4. **Sales & Sales Manager**: Widget draft quotation, quotation menunggu approval, dan kurs mingguan aktif.
5. **Customer Service**: Widget Job Order yang mendekati jadwal tiba (*ETA Approaching within 3 days*).
6. **Operation**: Widget Job Order yang masih berjalan/aktif (*In Progress / SPJM / SPPB*).

---

## 💼 3. Modul Customer & Vendor Register (Finance & Sales)

### A. Registrasi Customer
- **Navigasi**: `SALES & CUSTOMER` > `Customer` > `+ Tambah Customer`.
- **Kode Customer**: Dihasilkan otomatis oleh sistem dengan format standar (contoh: `CUS-2026-00001`).
- **Data Perusahaan**: Nama perusahaan, email penagihan, nomor telepon, alamat lengkap (tercantum di invoice), dan NPWP 15–16 digit.
- **Syarat Pembayaran**: Pilihan default term (`Cash`, `Net 7`, `Net 14`, `Net 30`, `Net 45`, `Net 60`, `Custom`).
- **Upload Dokumen**: Unggah berkas NPWP & NIB (PDF/JPG/PNG).
- **Kontak Shipper & Consignee**: Tambahkan daftar nama PIC, alamat consignee/shipper di pelabuhan untuk dicetak pada dokumen BL/AWB.

### B. Master Vendor
- **Navigasi**: `SALES & CUSTOMER` > `Vendor` > `+ Tambah Vendor`.
- **4 Kategori Vendor**:
  1. *Shipping Lines* (Pelayaran/Maskapai)
  2. *Vendor Trucking* (Armada darat)
  3. *Vendor Agent International*
  4. *Vendor Agent National*
- **Detail Vendor**: Kode vendor, nama vendor, kategori, NPWP/Tax ID, negara, rekening bank (BCA/Mandiri/lainnya), dan PIC.

---

## 📝 4. Modul Sales & Sales Manager

### A. Membuat Quotation Penawaran
- **Navigasi**: `SALES & CUSTOMER` > `Quotation` > `+ Buat Quotation`.
- **Langkah-langkah**:
  1. Pilih Customer dari master data.
  2. Tentukan Jenis Layanan (*Import Sea, Export Sea, Import Air, Export Air, Domestic, Land Trucking*).
  3. Pilih Terms of Delivery (Incoterms: *FOB, CIF, CFR, EXW, DDP, DDU, D2D*).
  4. Masukkan Pelabuhan Asal (*POL*) dan Pelabuhan Tujuan (*POD*).
  5. Masukkan rincian item biaya penawaran (Modal, Harga Jual, Qty, Satuan).
  6. Manfaatkan **Fitur Cepat / Quick Tariff**: Ambil tarif otomatis dari Master Trucking.
  7. Klik **Simpan Draft**.
  8. Ajukan penawaran dengan mengklik **Submit Approval**.

### B. Approval Quotation (Sales Manager)
- **Navigasi**: `SALES & CUSTOMER` > `Quotation` > Pilih quotation status *Submitted*.
- **Aksi**:
  - **Approve**: Menyetujui penawaran harga.
  - **Revise**: Mengembalikan ke Sales untuk revisi catatan/harga.
  - **Reject**: Menolak penawaran harga.
- Setelah *Approved*, tombol **Convert to Job Order** akan aktif untuk membuat Job Order otomatis ke tim CS & Operasional.

### C. Master Tarif Trucking (Trucking Price List)
- **Navigasi**: `PRICING & LOGISTIK` > `Trucking Price List`.
- **Format Tampilan Utama (Tabel)**:
  - `RUTE`: Pelabuhan Asal → Tujuan / Area.
  - `VENDOR TRUCKING`: Nama Vendor Armada (atau Umum).
  - `TGL BERLAKU`: Tanggal efektif awal s/d batas berlaku.
  - `STATUS`: Badge status Aktif / Nonaktif.
  - `AKSI`: Tombol **Lihat Detail (View)**, Edit, Toggle Status, dan Hapus.
- **Detail View Matriks Tarif Langsung (20GP / 40FT / 40HQ)**:
  - Menampilkan ringkasan matriks harga kontainer lengkap untuk:
    - **20GP / 20FT Trailer**: Tarif Normal (Modal & Harga Jual) serta Overweight (Modal & Harga Jual).
    - **40FT Trailer**: Tarif Normal (Modal & Harga Jual) serta Overweight (Modal & Harga Jual).
    - **40HQ / 40HC Trailer**: Tarif Normal (Modal & Harga Jual) serta Overweight (Modal & Harga Jual).

### D. Kalkulator Logistik
- **Kalkulator Pajak Impor**: Hitung Bea Masuk, PPN 11%, dan PPh 22 (API / Non-API / Manual).
- **Kalkulator Volume Weight & CBM**:
  - Rumus Volume Udara: $\text{Vol Weight (Kg)} = \frac{P \times L \times T}{6000}$
  - Rumus CBM Laut: $\text{CBM} = \frac{P \times L \times T}{1.000.000}$
- **Kalkulator Biaya LCL**: Perhitungan biaya berbasis perbandingan $W/M$ (berat tonase vs kubikasi CBM terbesar).

---

---

## 🚢 5. Penyederhanaan Layanan (5 Services) & Dokumen Operasional

Sistem telah disederhanakan menjadi **5 Layanan Utama (Canonical Services)** dengan set dokumen operasional otomatis yang disesuaikan:

1. **EXPORT SEA (`exp_sea`)**:
   - Set Dokumen: `JOB ORDER` | `SHIPPING INSTRUCTION` | `BOOKING CONFIRMATION` | `BILL OF LADING` | `TANDA TERIMA DOCUMENT`
2. **EXPORT AIR (`exp_air`)**:
   - Set Dokumen: `JOB ORDER` | `SHIPPING INSTRUCTION` | `BOOKING CONFIRMATION` | `AIRWAYBILL` | `TANDA TERIMA DOCUMENT`
3. **IMPORT SEA (`imp_sea`)** & **IMPORT AIR (`imp_air`)**:
   - Set Dokumen: `JOB ORDER` | `SK DO` | `SK PABEAN` | `DNP` | `SURAT JALAN` | `TANDA TERIMA DOCUMENT`
4. **DOMESTIC / TRUCKING (`domestic`)**:
   - Set Dokumen: `JOB ORDER` | `SURAT JALAN` | `TANDA TERIMA DOCUMENT`

---

## 📑 6. Tampilan Job Order: Menu Tab Horizontal ke Kanan (5 Tab)

Halaman detail Job Order kini menggunakan tampilan **Pill Tabs Navigasi ke Kanan**:

1. **Tab 1 — 1. Data Pengapalan**:
   - Format Job Order formal lengkap dengan kop perusahaan, JO. No, Tanggal, Type Layanan, Marketing/Sales PIC.
   - Tabel pihak terkait: Shipper / Consignee, Pelabuhan Muat (*POL*), Pelabuhan Bongkar (*POD*), ETD, ETA, Vessel/Flight, Quantity, Gross Weight, Volume, Commodity, dan Catatan (*NOTE*).
   - Riwayat status workflow job (*Draft → Open → Closed / Cancelled*).
2. **Tab 2 — 2. Customs & AJU**:
   - Kotak Nomor Pengajuan (*No AJU*) dan Nomor Pendaftaran (*Nopen*).
   - Banner visual status kepabeanan: **`STATUS: JALUR HIJAU (SPPB TERBIT)`** atau **`STATUS: JALUR MERAH (SPJM)`**.
   - Input/update nomor kepabeanan dan form pembaruan status shipment milestone.
3. **Tab 3 — 3. Dokumen (BL/CIPL)**:
   - Panel unduh dokumen PDF spesifik sesuai layanan (Export Sea/Air: SI, Booking Confirmation, BL/AWB; Import Sea/Air: SK DO, SK Pabean, DNP; Domestic: Surat Jalan & Tanda Terima).
   - Tabel upload berkas digital (*BL/AWB, Packing List, Invoice Dagang / CIPL, Dokumen Pabean*).
4. **Tab 4 — 4. Tanda Terima**:
   - Konfirmasi serah terima berkas dan status Delivery Order (DO).
   - Tombol cetak **Surat Jalan** dan **Tanda Terima Dokumen** langsung per Job Order.
   - Timeline pengiriman & log konfirmasi DO.
5. **Tab 5 — 5. Biaya & Profit**:
   - Estimasi Penawaran (Snapshot Quotation asal).
   - Ringkasan Keuangan Aktual (Modal Provision, Tagihan Jual, Laba Aktual, Margin %) khusus untuk Finance & Management.

---

## 💰 7. Modul Finance & Finance Manager

### A. Penginputan Biaya Aktual Job
- **Charges (Provisional Payment)**: Otomatis disalin dari item modal penawaran quotation.
- **Reimbursement (Temporary Payment)**: Pencatatan talangan biaya operasional/bea cukai/TPS di lapangan dengan melampirkan bukti transaksi.

### B. Closing Job & Penerbitan Invoice
- Saat pekerjaan selesai, lakukan **Closing Job** di menu `KEUANGAN` > `Closing Job`.
- Tentukan tanggal closing, tanggal jatuh tempo (*Due Date*), akun penampung dana, PPN, dan kurs konversi invoice (kurs mingguan atau override manual).
- Sistem akan otomatis membuat tagihan **Invoice Komersial** dan mencatat jurnal akuntansi piutang.

### C. Status Pengiriman Invoice Fisik
- Di halaman detail invoice, Finance dapat memperbarui status pengiriman fisik:
  - *Belum Dikirim* → *Terkirim ke Ekspedisi / Kurir* (isi nomor resi dan tanggal kirim) → *Diterima Customer*.

### D. Statement of Account (SOA) & Email Penagihan
- **Navigasi**: `Laporan Keuangan` > `Statement of Account`.
- Filter berdasarkan customer, tanggal periode, dan opsi *Unpaid Only*.
- Unduh dokumen **SOA PDF** atau kirim penagihan langsung ke email customer. Riwayat pengiriman otomatis tercatat pada tabel *Log Email SOA*.

### E. Ekspor Coretax XML
- **Navigasi**: `KEUANGAN` > `Invoice` > `Ekspor Coretax`.
- Pilih invoice komersial yang memiliki PPN > Unduh file format XML standar DJP untuk diunggah ke portal Coretax.

### F. Jurnal, Buku Besar, & Laporan Laba Rugi
- Jurnal entri otomatis seimbang (Debit = Kredit) untuk setiap transaksi closing, pembayaran, dan reimbursement.
- Finance Manager dapat mengakses laporan **Profit per Job (JO Profit)** dan **Profit Bulanan**.
