# MISSING FEATURES — JobFinance

Berisi fitur scope yang belum ada sama sekali (missing) atau komponen yang hilang (FE/BE/DB). Prioritas: H=high (aktif & terkait alur utama), M=medium, L=low.

| No | Feature | Missing FE | Missing BE | Missing DB | Priority |
| -- | ------- | ---------- | ---------- | ---------- | -------- |
| 36 | Master TPS Air/Sea (kota, nama TPS, kode TPS) | Ya (belum ada halaman) | Ya (belum ada controller/service) | Ya (belum ada tabel `tps`) | H |
| 41 | Invoice asli sudah dikirim (status NOT_SENT/SENT/RECEIVED + sent_at/received_at/tracking/notes) | Ya | Ya | Ya (kolom di `invoices`) | M |
| 39 | Generate DNP (Dokumen Nota Pabean) — template PDF | Ya | Ya (tambah method di engine yg ada) | N/A | M |
| 39 | Generate SK Pabean — template PDF | Ya | Ya | N/A | M |
| 24/40 | Invoice PDF (cetak invoice per nomor) | Ya | Ya | N/A | M |
| 40 | SOA PDF (unduh SOA) | Ya | Ya | N/A | L |
| 49 | Email log SOA (recipient, subject, sent_at, status, error_message, sent_by) | Ya (belum tampil) | Ya (belum tabel) | Ya (tabel `soa_email_logs` / reuse) | M |
| 26 | DO Selesai benar-benar berfungsi (button + method + kolom) | Ada (button rusak) | Ya (`confirmDo` + service) | Ya (`jobs.do_confirmed_at/by`) | H |
| 22/37 | Job Document upload/download berjalan (disk `private` belum ada) | Ada (form) | Ada (controller) tapi error | Ya (tambah disk config + metadata) | H |
| 8 | Update Customer Contact tidak error (kolom `lock_version`) | Ada (form) | Ada (controller) | Ya (kolom `lock_version` di `customer_contacts`) | H |
| — | Invoice status `draft` (brief: Draft→Issued→Partially Paid→Paid) — saat ini invoice langsung `issued` saat closing | – | – | – | L (opsional; brief asli) |
| 53 | `.env.example` (dev/staging/prod) | N/A | N/A | N/A | H (CI `tests.yml` rusak tanpanya) |
| 53 | Separation dev/staging/prod env | Ya | Ya | N/A | M |
| 54 | Deploy pipeline (frontend build + deploy) di CI | Ya | Ya | N/A | M |
| 55 | Konfigurasi web server (SSL, domain, reverse proxy) | Ya | Ya | N/A | M |
| 56 | Backup database + dokumen upload (scheduled) | Ya | Ya (routes/console.php tanpa Schedule) | N/A | H |
| 57 | Monitoring aplikasi/server/log/error | Ya | Ya | N/A | M |
| 58 | Dokumen test plan (test case, smoke, regression, integration) | Ya | N/A | N/A | M |
| 58 | Test upload Job Document & upload NPWP/NIB | N/A | N/A | N/A | H |
| 59 | UAT checklist per role & modul | Ya | N/A | N/A | M |
| 61 | Deployment/environment/backup/monitoring report | Ya | N/A | N/A | L |
| 62 | Sprint progress/risk/dependency/delivery report | Ya (hanya catatan STAGE) | N/A | N/A | L |
| 63 | QA/UAT & defect closure report | Ya | N/A | N/A | L |
| 64 | Dokumentasi API/Architecture/DB yang akurat (docs/IMPLEMENTATION.md stale) | Ya | N/A | N/A | M |
| 65 | User Guide per role (Sales, CS, Operation, Finance) | Ya | N/A | N/A | M |

**Catatan**:
- Fitur yang TIDAK masuk daftar ini (mis. SOA, Coretax, Reimbursement, Jurnal, Kalkulator, Pricing, Vendor, RBAC, Dashboard, PDF dasar) sudah terimplementasi — lihat `AUDIT_FEATURES.md`.
- Prioritas mengikuti dampak ke alur utama (Customer→Quotation→Job→Cost→Invoice→SOA→Payment→Report) dan kebutuhan scope aktif (36, 41, 39).