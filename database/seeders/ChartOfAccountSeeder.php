<?php

namespace Database\Seeders;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Akun mapping bawaan sistem (tetap gunakan kode konfigurasi default agar kompatibel)
            foreach (config('accounting.mappings') as $key => $settings) {
                $account = ChartOfAccount::withTrashed()->firstOrCreate(
                    ['code' => $settings['code']],
                    ['name' => $settings['name'], 'type' => $settings['type']]
                );
                if (! $account->trashed() && $account->type === $settings['type']) {
                    AccountMapping::firstOrCreate(['key' => $key], ['chart_of_account_id' => $account->id]);
                }
            }
            foreach (['2101' => ['Utang Usaha', 'liability'], '3101' => ['Modal Pemilik', 'equity']] as $code => [$name, $type]) {
                ChartOfAccount::withTrashed()->firstOrCreate(['code' => $code], compact('name', 'type'));
            }

            // 2. Struktur data COA lengkap dari RADIX SYSTEM (3 Halaman)
            $accounts = [
                // LEVEL 1: 1 - ASSET
                ['code' => '1', 'name' => 'ASSET', 'type' => 'asset', 'parent_code' => null, 'level' => 1],
                // Level 2: 11000 - Asset Lancar
                ['code' => '11000', 'name' => 'Asset Lancar', 'type' => 'asset', 'parent_code' => '1', 'level' => 2],
                // Level 3 & 4 under 11000
                ['code' => '11100', 'name' => 'Cash', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11101', 'name' => 'PETTY CASH (-)', 'type' => 'asset', 'parent_code' => '11100', 'level' => 4],
                ['code' => '11120', 'name' => 'Bank', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11121', 'name' => 'BCA IDR (240-0375-758)', 'type' => 'asset', 'parent_code' => '11120', 'level' => 4],
                ['code' => '11122', 'name' => 'MANDIRI IDR (115-00-1053704-3)', 'type' => 'asset', 'parent_code' => '11120', 'level' => 4],
                ['code' => '11123', 'name' => 'BCA USD (240-0386-172)', 'type' => 'asset', 'parent_code' => '11120', 'level' => 4],
                ['code' => '11130', 'name' => 'Piutang Customer', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11140', 'name' => 'Piutang Agent', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11150', 'name' => 'Piutang Lain-lain', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11160', 'name' => 'Piutang Karyawan', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11170', 'name' => 'Piutang Komisaris', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11180', 'name' => 'Temporary Payment', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11181', 'name' => 'Provisional Payment', 'type' => 'asset', 'parent_code' => '11180', 'level' => 4],
                ['code' => '11190', 'name' => 'Jaminan', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11191', 'name' => 'PPN Masukan', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11192', 'name' => 'PPH 23 Dimuka', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                ['code' => '11193', 'name' => 'PPH 21 Dimuka', 'type' => 'asset', 'parent_code' => '11000', 'level' => 3],
                // Level 2: 12000 - Asset Tidak Lancar
                ['code' => '12000', 'name' => 'Asset Tidak Lancar', 'type' => 'asset', 'parent_code' => '1', 'level' => 2],
                ['code' => '12100', 'name' => 'Peralatan', 'type' => 'asset', 'parent_code' => '12000', 'level' => 3],
                ['code' => '12200', 'name' => 'Akumulasi Peralatan', 'type' => 'asset', 'parent_code' => '12000', 'level' => 3],
                ['code' => '12300', 'name' => 'Kendaraan', 'type' => 'asset', 'parent_code' => '12000', 'level' => 3],
                ['code' => '12400', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'asset', 'parent_code' => '12000', 'level' => 3],

                // LEVEL 1: 2 - HUTANG
                ['code' => '2', 'name' => 'HUTANG', 'type' => 'liability', 'parent_code' => null, 'level' => 1],
                ['code' => '21000', 'name' => 'Hutang Vendor', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '22000', 'name' => 'Hutang Agent', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '23000', 'name' => 'Deposit Customer', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '23100', 'name' => 'Deposit Customer', 'type' => 'liability', 'parent_code' => '23000', 'level' => 3],
                ['code' => '23200', 'name' => 'Deposit Vendor / Agent', 'type' => 'liability', 'parent_code' => '23000', 'level' => 3],
                ['code' => '24000', 'name' => 'Hutang Bank', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '25000', 'name' => 'Hutang Lain-Lain', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '25100', 'name' => 'Hutang Lain-lain', 'type' => 'liability', 'parent_code' => '25000', 'level' => 3],
                ['code' => '26000', 'name' => 'PPH 21 Hutang Pajak', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '27000', 'name' => 'PPH 23 Hutang Pajak', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '28000', 'name' => 'PPN Keluaran', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '29000', 'name' => 'Hutang PPN', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],
                ['code' => '29100', 'name' => 'PPH 4(2) HUTANG PAJAK', 'type' => 'liability', 'parent_code' => '2', 'level' => 2],

                // LEVEL 1: 3 - MODAL
                ['code' => '3', 'name' => 'MODAL', 'type' => 'equity', 'parent_code' => null, 'level' => 1],
                ['code' => '31000', 'name' => 'Modal', 'type' => 'equity', 'parent_code' => '3', 'level' => 2],
                ['code' => '32000', 'name' => 'Laba Ditahan', 'type' => 'equity', 'parent_code' => '3', 'level' => 2],
                ['code' => '33000', 'name' => 'Prive', 'type' => 'equity', 'parent_code' => '3', 'level' => 2],

                // LEVEL 1: 4 - PENDAPATAN
                ['code' => '4', 'name' => 'PENDAPATAN', 'type' => 'revenue', 'parent_code' => null, 'level' => 1],
                ['code' => '41000', 'name' => 'Pendapatan Import', 'type' => 'revenue', 'parent_code' => '4', 'level' => 2],
                ['code' => '42000', 'name' => 'Pendapatan Ekspor', 'type' => 'revenue', 'parent_code' => '4', 'level' => 2],
                ['code' => '43000', 'name' => 'Pendapatan Domestic', 'type' => 'revenue', 'parent_code' => '4', 'level' => 2],
                ['code' => '44000', 'name' => 'Pendapatan Lain-Lain', 'type' => 'revenue', 'parent_code' => '4', 'level' => 2],

                // LEVEL 1: 5 - HARGA POKOK PENJUALAN
                ['code' => '5', 'name' => 'HARGA POKOK PENJUALAN', 'type' => 'cogs', 'parent_code' => null, 'level' => 1],
                ['code' => '51000', 'name' => 'HPP Import', 'type' => 'cogs', 'parent_code' => '5', 'level' => 2],
                ['code' => '52000', 'name' => 'HPP Ekspor', 'type' => 'cogs', 'parent_code' => '5', 'level' => 2],
                ['code' => '53000', 'name' => 'HPP Domestic', 'type' => 'cogs', 'parent_code' => '5', 'level' => 2],
                ['code' => '5400', 'name' => 'HPP SEWA', 'type' => 'cogs', 'parent_code' => '5', 'level' => 2],

                // LEVEL 1: 6 - BIAYA/PENGELUARAN
                ['code' => '6', 'name' => 'BIAYA/PENGELUARAN', 'type' => 'expense', 'parent_code' => null, 'level' => 1],
                // 61000 - Biaya Kantor
                ['code' => '61000', 'name' => 'Biaya Kantor', 'type' => 'expense', 'parent_code' => '6', 'level' => 2],
                ['code' => '61100', 'name' => 'Biaya Alat Tulis Kantor (ATK)', 'type' => 'expense', 'parent_code' => '61000', 'level' => 3],
                ['code' => '61200', 'name' => 'Biaya Perlengkapan Kantor', 'type' => 'expense', 'parent_code' => '61000', 'level' => 3],
                // 62000 - Biaya Operasional
                ['code' => '62000', 'name' => 'Biaya Operasional', 'type' => 'expense', 'parent_code' => '6', 'level' => 2],
                ['code' => '62100', 'name' => 'Biaya Gaji Karyawan / Training', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62110', 'name' => 'Biaya Tunjangan Hari Raya (THR)', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62200', 'name' => 'Biaya Parkir & Bensin', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62210', 'name' => 'Biaya Keamanan', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62220', 'name' => 'Biaya Pengiriman/Pengurusan Dokumen', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62230', 'name' => 'Biaya Keperluan Dapur', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62240', 'name' => 'Biaya Perjalanan Dinas', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62250', 'name' => 'Biaya Utilities', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62260', 'name' => 'Biaya Percetakan', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62270', 'name' => 'Biaya Pengurusan Dokumen', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62300', 'name' => 'Biaya Entertainment', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62310', 'name' => 'Biaya Kesejahteraan Karyawan', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62400', 'name' => 'Biaya Asuransi BPJS', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62500', 'name' => 'Biaya Izin Usaha', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62600', 'name' => 'Biaya Penyusutan Peralatan', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62700', 'name' => 'Biaya Konsultan / Seminar / Pelatihan', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62800', 'name' => 'Biaya Pemeliharaan Software', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62810', 'name' => 'Biaya Pemeliharaan', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                ['code' => '62900', 'name' => 'Biaya Pemasaran', 'type' => 'expense', 'parent_code' => '62000', 'level' => 3],
                // 63000 - Biaya Lain-Lain
                ['code' => '63000', 'name' => 'Biaya Lain-Lain', 'type' => 'expense', 'parent_code' => '6', 'level' => 2],
                ['code' => '63100', 'name' => 'Biaya lain-lain', 'type' => 'expense', 'parent_code' => '63000', 'level' => 3],
                ['code' => '63200', 'name' => 'Rugi Selisih Kurs', 'type' => 'expense', 'parent_code' => '63000', 'level' => 3],
                ['code' => '63300', 'name' => 'Selisih Pembulatan', 'type' => 'expense', 'parent_code' => '63000', 'level' => 3],
                // 64000 - Biaya/Pendapatan Diluar Usaha
                ['code' => '64000', 'name' => 'Biaya/Pendapatan Diluar Usaha', 'type' => 'expense', 'parent_code' => '6', 'level' => 2],
                ['code' => '64100', 'name' => 'Biaya Provisi dan Adminstrasi Bank', 'type' => 'expense', 'parent_code' => '64000', 'level' => 3],
                ['code' => '64200', 'name' => 'Biaya Pajak Bunga', 'type' => 'expense', 'parent_code' => '64000', 'level' => 3],
                ['code' => '64300', 'name' => 'Biaya Transfer Antar Banke', 'type' => 'expense', 'parent_code' => '64000', 'level' => 3],
                ['code' => '64400', 'name' => 'Pendapatan Jasa Giro & Bunga', 'type' => 'expense', 'parent_code' => '64000', 'level' => 3],
                // 65000 - Biaya Pajak
                ['code' => '65000', 'name' => 'Biaya Pajak', 'type' => 'expense', 'parent_code' => '6', 'level' => 2],
                ['code' => '65100', 'name' => 'Pph 4(2)', 'type' => 'expense', 'parent_code' => '65000', 'level' => 3],
                ['code' => '65200', 'name' => 'PPH 23', 'type' => 'expense', 'parent_code' => '65000', 'level' => 3],
                ['code' => '65300', 'name' => 'PPH 21', 'type' => 'expense', 'parent_code' => '65000', 'level' => 3],
                ['code' => '65400', 'name' => 'Biaya Pajak Tidak Dapat Dikreditkan', 'type' => 'expense', 'parent_code' => '65000', 'level' => 3],
                ['code' => '65500', 'name' => 'Biaya Denda Pajak', 'type' => 'expense', 'parent_code' => '65000', 'level' => 3],
            ];

            $idByCode = [];
            foreach ($accounts as $item) {
                $parentId = $item['parent_code'] ? ($idByCode[$item['parent_code']] ?? null) : null;
                $record = ChartOfAccount::withTrashed()->firstOrNew(['code' => $item['code']]);
                $record->name = $item['name'];
                $record->type = $item['type'];
                $record->parent_id = $parentId;
                $record->level = $item['level'];
                $record->save();
                $idByCode[$item['code']] = $record->id;
            }

            // Hubungkan legacy accounts dengan parent hirarki
            $parentMap = [
                '1101' => ['11100', 4],
                '1102' => ['11120', 4],
                '1103' => ['11000', 3],
                '1104' => ['11000', 3],
                '1105' => ['11000', 3],
                '2101' => ['21000', 3],
                '2102' => ['21000', 3],
                '3101' => ['3', 2],
                '4101' => ['4', 2],
                '5101' => ['5', 2],
                '6101' => ['6', 2],
            ];

            foreach ($parentMap as $code => [$pCode, $lvl]) {
                if (isset($idByCode[$pCode])) {
                    ChartOfAccount::where('code', $code)->whereNull('parent_id')->update([
                        'parent_id' => $idByCode[$pCode],
                        'level' => $lvl,
                    ]);
                }
            }
        });
    }
}
