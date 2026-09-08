# Tahap 6 dan 7 — Closing, Invoice, Jurnal, dan Pembayaran

Finance dapat menutup Job `open` setelah seluruh biaya berstatus `final`. Closing berjalan dalam satu transaksi dan menghasilkan snapshot customer/biaya, invoice `issued`, jurnal otomatis, status Job `closed`, dan activity log.

Snapshot menyimpan nilai temporary, modal/nilai jual provision, subtotal, pajak, total, profit, margin, dan sumber dana. Perubahan data master setelah closing tidak mengubah dokumen historis.

Jurnal kapitalisasi mendebit Temporary/WIP dan mengkredit Kas atau Bank. Jurnal closing mendebit Piutang dan HPP, lalu mengkredit Temporary/WIP, Pendapatan, dan Utang Pajak. Akun berasal dari mapping COA.

Pembayaran dapat parsial atau penuh. Setiap pembayaran memperbarui saldo/status invoice, membuat jurnal Debit Kas/Bank dan Kredit Piutang, memakai optimistic locking, menolak overpayment, dan membuat activity log.

Dashboard kini mengambil piutang dari invoice serta pendapatan, HPP, dan profit dari snapshot closing. Daftar Pembayaran menampilkan invoice yang masih memiliki saldo.

Closing dan pembayaran memakai transaksi, row lock, nomor dokumen transaksional, validasi jurnal seimbang, batas nominal decimal, validasi tanggal, dan rollback penuh. Finance dan Super Admin mempunyai akses; Operational dan Management ditolak pada URL langsung.

`ClosingPaymentTest` mencakup snapshot historis, invoice, jurnal debit-kredit, pembayaran parsial/lunas, overpayment, optimistic locking, duplikasi closing, akses per role, dashboard, dan rollback ketika audit gagal.
