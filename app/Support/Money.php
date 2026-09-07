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
}
