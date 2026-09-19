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
            self::Draft => 'Draft',self::Submitted => 'Submitted',self::Revision => 'Revision',self::Approved => 'Accept',self::Rejected => 'Reject',self::Converted => 'Converted'
        };
    }
}
