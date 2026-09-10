# Tahap 11 — Statement of Account, Reimbursement, Kurs Invoice, dan Coretax

Tahap ini menambahkan laporan Statement of Account, modul Reimbursement, sumber biaya otomatis dari quotation, invoice dalam mata uang asing, laporan Profit Bulanan, dan ekspor faktur PPN ke XML Coretax.

Hasil akhir: 105 test dengan 1393 assertion lulus, Pint bersih, Blade terkompilasi, dan migration lokal berstatus Ran.

## Statement of Account (C1)

Laporan SOA mengagregasi seluruh invoice dikurangi pembayaran per customer sampai tanggal laporan. Hanya invoice yang sudah closing dan histori pembayarannya yang dihitung.

- Ringkasan menampilkan saldo berjalan, total penagihan, total pembayaran, dan aging buckets 0–30, 31–60, 61–90, serta lebih dari 90 hari berdasarkan jatuh tempo.
- Rincian menampilkan daftar tagihan dengan saldo berjalan dan perhitungan periode.
- ReportFilterRequest membatasi tanggal/tipe agar valid; route dilindungi `reports.view` dan hanya Finance/Management/Super Admin yang dapat membuka (Operational ditolak).

## Reimbursement (C2)

Finance/Super Admin mengelola penggantian biaya: mengajukan request, menyetujui, menolak, dan membayar. Alur status `requested → approved → paid` dan `requested → rejected`.

- Nomor unik dari document sequence, validasi tanggal/jumlah/customer, dan optimistic locking pada setiap transisi.
- Pembayaran menerima `paid_date`; tanggal mundur ditolak. Transisi menyimpan activity log `reimbursement.requested/approved/rejected/paid`.
- Jurnal pengeluaran dibentuk menggunakan mapping COA. Beban dibukukan pada account temporary/provision sesuai jenis atau beban operasional; pembulatan mengikuti Money presisi.
- Pengujian `ReimbursementTest` mencakup alur lengkap, validasi, transisi dan role, serta filter index.

## Biaya otomatis dari quotation (C3)

Saat Job Order dibuka (`open`), sistem mengisi biaya draft awal dari rincian quotation ketika job belum memiliki biaya apapun, sehingga Finance tinggal finalisasi.

- Setiap item quotation menghasilkan baris `job_costs` draft dengan tipe Temporary/Provision, satuan, quantity, modal, dan nilai jual.
- Nilai dan kurs per item mengikuti snapshot quotation: saat quotation dibuat, item menyimpan `currency` dan `exchange_rate` dari header. Kurs dipakai hanya bila bukan IDR dan dijamin tidak nol.
- Aktivitas dicatat per biaya (`job_cost.created`) dan ringkasan seed (`job.costs.seeded`).
- Job tanpa snapshot quotation diproses dengan aman tanpa membuat biaya.
- Pengujian `JobQuotationChargesTest` mencakup seed IDR, seed asing dengan kurs, dan snapshot kosong.

## Kurs pada invoice dan snapshot (C4)

Invoice inherits mata uang dan kurs dari header quotation pada saat closing; buku besar tetap IDR, sedangkan nilai asing ditampilkan sebagai keterangan ekuivalen.

- Migration menambah `currency` (default IDR) dan `exchange_rate` (default 1) pada `invoices` dan `job_closing_snapshots`.
- `JobClosingService` membaca currency/kurs dari snapshot quotation, memvalidasinya terhadap daftar mata uang aktif dan menolak kurs nol, lalu menuliskannya ke snapshot closing dan invoice.
- Model menyediakan konversi ke mata uang invoice (`inInvoiceCurrency`) dengan pembulatan HalfUp dua desimal.
- Tampilan invoice menampilkan baris Mata Uang dan Kurs terhadap Rupiah, plus blok keterangan "setara" untuk nilai asing; index menampilkan total asing dengan nilai IDR di sampingnya.
- Pengujian `ExchangeRateInvoiceTest` mencakup invoice USD dengan ekuivalen, default IDR, dan penolakan kurs nol.

## Laporan Profit Bulanan (C5)

Laporan Profit Bulanan mengelompokkan profit per job (dari snapshot closing historis) menurut bulan closing pada tahun kalender yang dipilih.

- `FinancialReportService::monthlyProfit` menghasilkan 12 baris (Januari–Desember) berisi jumlah job, temporary, modal, nilai jual, revenue, profit, dan margin, serta baris total.
- Tahun dibatasi 2000–2100 dan default tahun berjalan. Route `/reports/profit-bulanan` berlabel "Profit Bulanan" pada menu Laporan Keuangan.
- Pengujian `MonthlyProfitReportTest` mencakup pengelompokan bulanan dengan total, filter tahun, dan akses role (Management ditolak menulis, Operational tidak membaca).

## Ekspor XML Coretax (C6)

Faktur yang memiliki PPN (tax > 0) dapat diekspor ke satu file XML Coretax langsung dari halaman detail invoice.

- `CoretaxService` membangun XML `CoretaxImport` dengan DOMDocument: DocumentInfo (FakturKeluaran), Seller dari `config('accounting.coretax')`, Buyer dari snapshot customer (NPWP dari `tax_number`, nama, alamat), Invoice (NoFaktur, tanggal, jatuh tempo, JenisTransaksi 01, StatusFaktur Normal), Items (uraian, quantity, satuan, harga satuan, jumlah), dan Summary (SubTotal, TotalDiscount, DPP, PPN, PpnRate = PPN/DPP × 100, Currency, ExchangeRate, GrandTotal).
- DPP dan PPN seluruhnya IDR; PpnRate dihitung dari nominal (misal 11.58% untuk PPN Rp1.100.000 atas DPP Rp9.500.000).
- Ekspor ditolak bila PPN atau DPP nol, dicatat sebagai activity log `invoice.coretax.exported`, dan route dilindungi `invoices.manage` sehingga Management/Operational ditolak.
- Tombol "Ekspor XML Coretax" hanya tampil untuk invoice ber-PPN.
- Pengujian `CoretaxExportTest` mencakup konten XML seller/buyer/ringkasan, penolakan invoice bebas pajak, dan akses role.