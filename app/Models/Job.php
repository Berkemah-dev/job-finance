<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Job extends Model
{
    use HasFactory;

    public function costs(): HasMany
    {
        return $this->hasMany(JobCost::class);
    }

    public function closingSnapshot(): HasOne
    {
        return $this->hasOne(JobClosingSnapshot::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quotation_snapshot' => 'array', 'job_date' => 'date', 'expected_completion_date' => 'date', 'opened_at' => 'datetime', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
