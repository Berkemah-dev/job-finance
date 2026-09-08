# Tahap 8 dan 9 — Akuntansi dan Laporan Keuangan

## Jurnal dan koreksi

Modul Jurnal menampilkan seluruh jurnal posted dari kapitalisasi biaya, closing, pembayaran customer, penyesuaian, dan reversal. Detail jurnal menampilkan akun serta debit/kredit dan tidak menyediakan edit atau hapus.

Finance dapat membuat jurnal penyesuaian yang langsung diposting. Setiap baris wajib memiliki tepat satu sisi debit atau kredit, seluruh akun harus aktif, total debit harus sama dengan kredit, dan nominal tidak boleh nol. Koreksi jurnal dilakukan dengan reversal yang membalik seluruh baris, menautkan jurnal asal dan jurnal reversal, memakai optimistic locking, serta membuat activity log.

## Laporan tahap 8

- Buku Besar menampilkan saldo awal, mutasi, dan saldo berjalan per akun serta filter periode.
- Neraca Saldo menampilkan saldo debit/kredit seluruh akun sampai tanggal laporan.
- Angka laporan dihitung dari `journal_entries` posted dan tetap mencakup akun yang kemudian diarsipkan.

## Laporan tahap 9

- Neraca menghitung Aset, Liabilitas, Ekuitas, dan laba berjalan.
- Laba Rugi menghitung Pendapatan, HPP, laba kotor, beban, dan laba bersih per periode.
- Arus Kas mengelompokkan penerimaan customer, pengeluaran temporary/provision, penyesuaian, dan transaksi lain pada akun Kas/Bank yang dipilih melalui mapping COA.
- Profit per Job memakai snapshot closing historis: temporary, modal provision, nilai jual provision, profit, dan margin.
- Dashboard menampilkan grafik pendapatan dan profit enam bulan, angka keuangan aktual, dan invoice belum lunas.

Finance dan Super Admin dapat mengelola jurnal serta membaca laporan. Management memiliki akses baca laporan. Operational tidak memiliki akses ke jurnal maupun laporan keuangan.

Pengujian `AccountingReportTest` mencakup Buku Besar, Neraca Saldo seimbang, Neraca, Laba Rugi, Arus Kas, Profit per Job, jurnal penyesuaian, reversal, akun arsip, akses role, dan keterhubungan dashboard.
