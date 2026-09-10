<?php

namespace App\Services;

use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class CalculationService
{
    public const VOLUME_WEIGHT_DIVISOR = 6000;

    public const CBM_DIVISOR = 1000000;

    /**
     * Volume weight ((panjang × lebar × tinggi) / divisor). Default 6000 utk kargo udara.
     */
    public function volumeWeight(float|int $lengthCm, float|int $widthCm, float|int $heightCm, int $divisor = self::VOLUME_WEIGHT_DIVISOR): float
    {
        $this->assertDimensions($lengthCm, $widthCm, $heightCm, $divisor);

        return round(($lengthCm * $widthCm * $heightCm) / $divisor, 2);
    }

    public function cbm(float|int $lengthCm, float|int $widthCm, float|int $heightCm): float
    {
        $this->assertDimensions($lengthCm, $widthCm, $heightCm);

        return round(($lengthCm * $widthCm * $heightCm) / self::CBM_DIVISOR, 4);
    }

    /**
     * Berat tagihan = maksimum berat kotor vs volume weight.
     */
    public function chargeableWeight(float|int $grossWeightKg, float|int $volumeWeightKg): float
    {
        return round(max((float) $grossWeightKg, (float) $volumeWeightKg), 2);
    }

    /**
     * Hitung satu baris paket berdasarkan qty.
     */
    public function package(int $qty, float|int $lengthCm, float|int $widthCm, float|int $heightCm, float|int $grossWeightKg, int $divisor = self::VOLUME_WEIGHT_DIVISOR): array
    {
        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Qty minimal 1.']);
        }
        $volumeWeight = $this->volumeWeight($lengthCm, $widthCm, $heightCm, $divisor);

        return [
            'qty' => $qty,
            'volume_weight_per_unit' => $volumeWeight,
            'cbm_per_unit' => $this->cbm($lengthCm, $widthCm, $heightCm),
            'gross_weight_per_unit' => round((float) $grossWeightKg, 2),
            'volume_weight' => round($volumeWeight * $qty, 2),
            'cbm' => round($this->cbm($lengthCm, $widthCm, $heightCm) * $qty, 4),
            'gross_weight' => round((float) $grossWeightKg * $qty, 2),
            'chargeable_weight' => $this->chargeableWeight((float) $grossWeightKg * $qty, $volumeWeight * $qty),
        ];
    }

    /**
     * Agregasi total untuk beberapa baris paket.
     */
    public function packageTotals(array $packages): array
    {
        $rows = [];
        $gross = 0.0;
        $volume = 0.0;
        $cbm = 0.0;
        foreach ($packages as $index => $package) {
            if (! ($package['qty'] ?? 0)) {
                continue;
            }
            $row = $this->package(
                (int) $package['qty'],
                (float) ($package['length'] ?? 0),
                (float) ($package['width'] ?? 0),
                (float) ($package['height'] ?? 0),
                (float) ($package['gross_weight'] ?? 0)
            );
            $rows[] = $row;
            $gross += $row['gross_weight'];
            $volume += $row['volume_weight'];
            $cbm += $row['cbm'];
        }

        return [
            'rows' => $rows,
            'package_count' => collect($rows)->sum('qty'),
            'total_gross_weight' => round($gross, 2),
            'total_volume_weight' => round($volume, 2),
            'total_cbm' => round($cbm, 4),
            'total_chargeable_weight' => $this->chargeableWeight($gross, $volume),
        ];
    }

    /**
     * Hitung pajak dari nilai dasar dan persentase.
     */
    public function tax(string|float|int $baseAmount, string|float|int $ratePercent): array
    {
        $base = Money::decimal($baseAmount);
        $rate = (float) $ratePercent;
        if ($rate < 0 || $rate > 100) {
            throw ValidationException::withMessages(['rate' => 'Persentase pajak harus antara 0 dan 100.']);
        }
        $tax = $base->multipliedBy($rate)->dividedBy('100', 2, RoundingMode::HALF_UP);

        return [
            'base' => Money::checked($base),
            'rate' => $rate,
            'tax' => Money::checked($tax),
            'total' => Money::checked($base->plus($tax)),
        ];
    }

    /**
     * Estimasi biaya LCL basis volume (W/M): tagihan = maksimum total m³ vs tonase (1.000 kg),
     * dikalikan tarif per m³. Tarif kosong memakai default dari config operations.lcl.default_rate.
     */
    public function lcl(array $packages, string|float|int|null $ratePerCbm = null): array
    {
        $totals = $this->packageTotals($packages);
        $rate = Money::decimal((float) ($ratePerCbm ?? config('operations.lcl.default_rate')));
        $cbmBasis = (float) $totals['total_cbm'];
        $weightBasis = (float) $totals['total_gross_weight'] / 1000;
        $chargeableBasis = max($cbmBasis, $weightBasis);
        $cost = $rate->multipliedBy((string) round($chargeableBasis, 4))->toScale(2, RoundingMode::HALF_UP);

        return [
            'rows' => $totals['rows'],
            'package_count' => $totals['package_count'],
            'total_gross_weight' => $totals['total_gross_weight'],
            'total_volume_weight' => $totals['total_volume_weight'],
            'total_cbm' => $totals['total_cbm'],
            'chargeable_basis' => round($chargeableBasis, 4),
            'basis_note' => count($packages) ? ($cbmBasis >= $weightBasis ? 'm³' : 'tonase') : null,
            'rate_per_cbm' => Money::checked($rate),
            'total_cost' => Money::checked($cost),
        ];
    }

    private function assertDimensions(float|int ...$values): void
    {
        foreach ($values as $value) {
            if ($value < 0) {
                throw ValidationException::withMessages(['dimension' => 'Dimensi tidak boleh negatif.']);
            }
        }
    }
}
