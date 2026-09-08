# Tahap 10 — Hardening, Dokumentasi, dan QA

Tahap akhir melengkapi factory untuk snapshot closing, invoice/item, pembayaran, jurnal/entry, dan sumber penyesuaian. Seeder lokal membuat role/permission, mapping COA, akun pengguna untuk setiap role, serta satu alur demo lengkap dari Customer sampai pembayaran parsial dan laporan.

Administrasi pengguna mendukung tambah/edit user, pemilihan role, perubahan password opsional, email unik, optimistic locking, activity log, dan proteksi agar Super Admin terakhir tidak dapat diturunkan rolenya.

Validasi akhir mencakup filter periode laporan, filter jurnal, akun aktif pada penyesuaian, jurnal seimbang, batas tanggal, stale submission, dan akses URL langsung. Management hanya membaca laporan; Finance mengelola transaksi akuntansi; Operational tidak membaca laporan keuangan; Super Admin mengelola pengguna.

QA mencakup seluruh feature test, Laravel Pint, kompilasi Blade, build production Vite, migration MySQL, seeding idempoten, pemeriksaan route, serta smoke test antarmuka desktop dan mobile.

Hasil akhir: 62 test dengan 770 assertion lulus, Pint lulus, Blade cache berhasil, build Vite berhasil, seluruh migration MySQL berstatus Ran, dan seeder kedua tidak menggandakan data demo. QA browser memverifikasi aset Poppins lokal dan layout login pada viewport desktop 1440×900 serta responsif 500×844. Pemeriksaan MySQL juga menemukan lalu memperbaiki field request yang sempat ikut terisi saat model guard dinonaktifkan oleh seeder; service sekarang selalu memilih field bisnis secara eksplisit.
