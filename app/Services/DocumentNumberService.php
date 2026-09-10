<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    // The caller must hold a database transaction. No MAX(id) or random numbers.
    public function next(string $type): string
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Document numbering requires a transaction.');
        }
        $period = now()->format('Ym');
        DB::table('document_sequences')->insertOrIgnore(['type' => $type, 'period' => $period, 'counter' => 0]);
        $sequence = DB::table('document_sequences')->where('type', $type)->where('period', $period)->lockForUpdate()->first();
        $next = $sequence->counter + 1;
        DB::table('document_sequences')->where('id', $sequence->id)->update(['counter' => $next]);

        return strtoupper($type).'-'.$period.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // Kode master dengan periode tahunan (mis. CUS-2026-00001) agar unique di bawah koncurrency.
    public function nextYear(string $key, ?string $prefix = null, string $delimiter = '-', int $pad = 5, ?int $year = null): string
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Document numbering requires a transaction.');
        }
        $period = (string) ($year ?? now()->year);
        DB::table('document_sequences')->insertOrIgnore(['type' => $key, 'period' => $period, 'counter' => 0]);
        $sequence = DB::table('document_sequences')->where('type', $key)->where('period', $period)->lockForUpdate()->first();
        $next = $sequence->counter + 1;
        DB::table('document_sequences')->where('id', $sequence->id)->update(['counter' => $next]);

        return ($prefix ?? strtoupper($key)).$delimiter.$period.$delimiter.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
