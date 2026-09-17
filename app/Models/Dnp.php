<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dnp extends Model
{
    use HasFactory;

    protected $table = 'dnps';
    protected $guarded = ['id'];

    protected $casts = [
        'dnp_date'                => 'date',
        'invoice_value'           => 'decimal:2',
        'freight'                 => 'decimal:2',
        'insurance'               => 'decimal:2',
        'total_value'             => 'decimal:2',
        'is_repeated_transaction' => 'boolean',
        'supporting_documents'    => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNumber(?string $prefix = null): string
    {
        $prefix = $prefix ?: 'DNP/' . date('Ym') . '/';
        $count = static::whereYear('dnp_date', date('Y'))->count() + 1;
        return $prefix . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    public static function defaultSupportingDocuments(): array
    {
        return [
            1  => 'Invoice',
            2  => 'Packing List',
            3  => "Kontrak Penjualan (Sale's Contract)",
            4  => 'Purchase Order/Confirmation Order',
            5  => 'L/C',
            6  => 'Rekening Koran yang terkait dengan transaksi tersebut',
            7  => 'Rekening Koran yang terdapat pelunasan transaksi sebelum nya',
            8  => 'Bukti Transfer',
            9  => 'Bukti hutang kepada supplier dalam hal barang belum jatuh tempo',
            10 => 'Bukti negosiasi harga',
            11 => 'Bukti pembayaran atas barang yang sama pada supplier yang sama untuk transaksi yang sama untuk transaksi sebelumnya',
            12 => 'Sales contract untuk transaksi yang telah lalu atas barang yang sama',
            13 => 'Perjanjian penunjukan agen penjual/pembelian/broker',
            14 => 'Kontrak pembuatan pengemasan dan/atau pengepakan',
            15 => 'Kontrak pembuatan barang impor dengan material yang dipasok oleh pembeli dariDaerah Pabean atau dari luar Daerah Pabean (assist',
            16 => 'Perjanjian pembayaran royalty atau lisensi',
            17 => 'Bukti bayar ongkos angkutan dalam hal FOB/exwork/....',
            18 => 'Perjanjian pembayaran Procceds',
            19 => 'Kontrak pengangkutan',
            20 => 'Kontrak Asuransi',
            21 => 'Laporan hasil audit kepabeanan 2 (dua) tahun terakhir',
        ];
    }
}
