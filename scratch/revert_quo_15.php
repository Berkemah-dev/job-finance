<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = App\Models\Quotation::find(15);
if ($q && $q->job && $q->job->status === 'cancelled') {
    $q->status = App\Enums\QuotationStatus::Approved;
    $q->save();
    $q->statusHistory()->create([
        'from_status' => 'converted',
        'to_status' => 'approved',
        'note' => 'Status disesuaikan kembali ke Approved karena Job Order ' . $q->job->number . ' telah dibatalkan.',
        'user_id' => $q->job->cancelled_by ?? 1,
        'created_at' => now(),
    ]);
    echo "Quotation 15 status reverted to: " . $q->status->value . "\n";
} else {
    echo "No change needed or quotation 15 not found.\n";
}
