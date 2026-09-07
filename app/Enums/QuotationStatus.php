<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',self::Submitted => 'Diajukan',self::Approved => 'Disetujui',self::Rejected => 'Ditolak',self::Converted => 'Dikonversi'
        };
    }
}
