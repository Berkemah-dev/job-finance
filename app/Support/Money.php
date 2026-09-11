<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class Money
{
    public static function decimal(string|int $value): BigDecimal
    {
        return BigDecimal::of($value)->toScale(2, RoundingMode::HalfUp);
    }

    public static function checked(BigDecimal $value): string
    {
        if ($value->abs()->isGreaterThan('9999999999999999.99')) {
            throw ValidationException::withMessages(['items' => 'Total nominal melebihi batas. Kurangi jumlah atau nilai item.']);
        }

        return (string) $value->toScale(2, RoundingMode::HalfUp);
    }

    public static function format(string|int|null $value): string
    {
        $number = (string) self::decimal($value ?? '0');
        [$whole,$fraction] = explode('.', $number);

        return preg_replace('/\\B(?=(\\d{3})+(?!\\d))/', '.', $whole).','.$fraction;
    }

    public static function terbilang(string|int|float|null $value, string $currency = 'IDR'): string
    {
        $num = (float) abs((float) ($value ?? 0));
        $whole = (int) floor($num);

        $words = self::convertNumberToWords($whole);
        if ($words === '') {
            $words = 'Nol';
        }

        $currencyName = match (strtoupper($currency)) {
            'IDR' => 'Rupiah',
            'USD' => 'Dolar Amerika Serikat',
            'SGD' => 'Dolar Singapura',
            'EUR' => 'Euro',
            default => $currency,
        };

        return trim($words.' '.$currencyName);
    }

    private static function convertNumberToWords(int $number): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($number < 12) {
            return $words[$number];
        }
        if ($number < 20) {
            return $words[$number - 10].' Belas';
        }
        if ($number < 100) {
            $tens = (int) floor($number / 10);
            $remainder = $number % 10;

            return trim($words[$tens].' Puluh '.$words[$remainder]);
        }
        if ($number < 200) {
            return trim('Seratus '.self::convertNumberToWords($number - 100));
        }
        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $remainder = $number % 100;

            return trim($words[$hundreds].' Ratus '.self::convertNumberToWords($remainder));
        }
        if ($number < 2000) {
            return trim('Seribu '.self::convertNumberToWords($number - 1000));
        }
        if ($number < 1000000) {
            $thousands = (int) floor($number / 1000);
            $remainder = $number % 1000;

            return trim(self::convertNumberToWords($thousands).' Ribu '.self::convertNumberToWords($remainder));
        }
        if ($number < 1000000000) {
            $millions = (int) floor($number / 1000000);
            $remainder = $number % 1000000;

            return trim(self::convertNumberToWords($millions).' Juta '.self::convertNumberToWords($remainder));
        }
        if ($number < 1000000000000) {
            $billions = (int) floor($number / 1000000000);
            $remainder = $number % 1000000000;

            return trim(self::convertNumberToWords($billions).' Miliar '.self::convertNumberToWords($remainder));
        }
        if ($number < 1000000000000000) {
            $trillions = (int) floor($number / 1000000000000);
            $remainder = $number % 1000000000000;

            return trim(self::convertNumberToWords($trillions).' Triliun '.self::convertNumberToWords($remainder));
        }

        return (string) $number;
    }
}
