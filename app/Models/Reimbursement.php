<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reimbursement extends Model
{
    protected $fillable = [
        'number', 'employee_id', 'job_id', 'vendor_id', 'category', 'reimbursement_date', 'description', 'notes', 'amount',
        'currency', 'exchange_rate', 'attachment_name', 'attachment_path',
        'status', 'payment_reference', 'paid_date', 'funding_account_id', 'created_by',
        'reviewed_by', 'reviewed_at', 'paid_at', 'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'reimbursement_date' => 'date',
            'paid_date' => 'date',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'currency' => 'string',
            'exchange_rate' => 'decimal:2',
            'lock_version' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fundingAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'funding_account_id');
    }

    public function journals(): MorphMany
    {
        return $this->morphMany(Journal::class, 'source');
    }
}
