<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoaEmailLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['customer_id', 'recipients', 'subject', 'period_from', 'period_to', 'status', 'error_message', 'sent_by', 'sent_at'];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'period_from' => 'date',
            'period_to' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
