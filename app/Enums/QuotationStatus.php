<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Revision = 'revision';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',self::Submitted => 'Diajukan',self::Revision => 'Revisi',self::Approved => 'Disetujui',self::Rejected => 'Ditolak',self::Converted => 'Dikonversi'
        };
    }
}
