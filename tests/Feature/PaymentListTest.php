<?php

namespace Tests\Feature;

use App\Models\JobCost;
use App\Services\PaymentListService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentListTest extends TestCase
{
    public function test_payment_filters_and_outstanding_amounts_use_active_journals(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.payment_list_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('payment_list_test');
        try {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('number');
            });
            Schema::create('job_costs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id');
                $table->string('number');
                $table->string('cost_category')->nullable();
                $table->date('cost_date');
                $table->decimal('total_cost', 18, 2);
                $table->timestamp('paid_at')->nullable();
                $table->softDeletes();
            });
            Schema::create('journals', function (Blueprint $table) {
                $table->id();
                $table->string('source_type');
                $table->unsignedBigInteger('source_id');
                $table->string('type');
                $table->timestamp('reversed_at')->nullable();
                $table->unsignedBigInteger('reversal_of_id')->nullable();
            });
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('journal_id');
                $table->string('description')->nullable();
                $table->decimal('debit', 18, 2)->default(0);
                $table->decimal('credit', 18, 2)->default(0);
            });
            DB::table('jobs')->insert([['id' => 1, 'number' => 'JOB-001'], ['id' => 2, 'number' => 'JOB-002']]);
            foreach ([
                [1, 'CN-001', 'credit_note', 1, null, null],
                [2, 'PR-001', 'payment_request', 1, '2026-10-22 10:00:00', null],
                [3, 'DN-001', 'debit_note', 2, '2026-10-22 10:00:00', null],
                [4, 'PR-LEGACY', null, 2, null, null],
                [5, 'CN-DELETED', 'credit_note', 1, null, '2026-10-22 10:00:00'],
                [6, 'RB-001', 'reimbursement', 1, null, null],
            ] as [$id, $number, $category, $jobId, $paidAt, $deletedAt]) {
                DB::table('job_costs')->insert(['id' => $id, 'number' => $number, 'cost_category' => $category, 'job_id' => $jobId, 'cost_date' => '2026-10-22', 'total_cost' => 1000, 'paid_at' => $paidAt, 'deleted_at' => $deletedAt]);
            }
            $journal = function ($id, $costId, $type, $amount, $description, $reversed = null) {
                DB::table('journals')->insert(['id' => $id, 'source_type' => JobCost::class, 'source_id' => $costId, 'type' => $type, 'reversed_at' => $reversed]);
                DB::table('journal_entries')->insert(['journal_id' => $id, 'description' => $description, 'credit' => $type === 'job_cost_payment' ? $amount : 0, 'debit' => $type === 'job_cost_payment' ? 0 : $amount]);
            };
            $journal(1, 3, 'job_cost_payment', 600, 'Pembayaran biaya');
            $journal(2, 3, 'job_cost_payment', 600, 'Reklasifikasi Provisi biaya susulan DN-001');
            $journal(3, 3, 'job_cost_payment', 1000, 'Pembayaran lama', '2026-10-22 09:00:00');
            $journal(4, 2, 'job_cost_finalization', 1000, 'Pengakuan biaya');
            $service = new PaymentListService;
            $ids = fn ($filters) => $service->query($filters)->reorder()->orderBy('id')->pluck('id')->all();
            $this->assertSame([1, 2, 3, 4, 6], $ids([]));
            $this->assertSame([1], $ids(['category' => 'credit_note']));
            $this->assertSame([2, 4], $ids(['category' => 'payment_request']));
            $this->assertSame([3], $ids(['category' => 'debit_note', 'job_number' => '002', 'number' => 'DN-001']));
            $this->assertSame([6], $ids(['category' => 'reimbursement']));
            $this->assertSame([2], $ids(['status' => 'paid']));
            $this->assertSame([1, 3, 4, 6], $ids(['status' => 'unpaid']));
            $this->assertSame([3], $ids(['status' => 'partial']));
            $cost = $service->query(['number' => 'DN-001'])->withoutEagerLoads()->firstOrFail();
            $this->assertEquals(600, $cost->list_paid_amount);
            $this->assertSame(5, $service->query()->paginate(2)->total());
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('payment_list_test');
        }
    }
}
