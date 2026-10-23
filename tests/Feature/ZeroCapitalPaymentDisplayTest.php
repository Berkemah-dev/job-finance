<?php

namespace Tests\Feature;

use App\Http\Controllers\JobCostController;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\User;
use App\Services\JobCostService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ZeroCapitalPaymentDisplayTest extends TestCase
{
    private function scenario(callable $test): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.zero_capital_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('zero_capital_test');
        try {
            Artisan::call('migrate', ['--database' => 'zero_capital_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $this->actingAs($finance);
            $job = Job::factory()->open()->create(['service_type' => 'imp_sea']);
            $test($job, $finance, app(JobCostService::class));
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('zero_capital_test');
        }
    }

    public function test_zero_capital_hides_payment_labels_but_preserves_positive_capital_statuses(): void
    {
        $this->scenario(function ($job, $finance, $service) {
            $base = ['job_id' => $job->id, 'status' => 'final', 'unit_cost' => '0', 'total_cost' => '0',
                'foreign_unit_cost' => '0', 'foreign_total_cost' => '0', 'total_price' => '180000',
                'created_by' => $finance->id, 'updated_by' => $finance->id];
            $zero = JobCost::factory()->create($base);
            $foreignZero = JobCost::factory()->create(array_replace($base, ['currency' => 'USD', 'cost_exchange_rate' => '16000', 'price_exchange_rate' => '18000']));
            $legacyZero = JobCost::factory()->create(array_replace($base, ['paid_at' => now(), 'paid_date' => today()]));
            $paid = JobCost::factory()->create(array_replace($base, ['unit_cost' => '1000000', 'total_cost' => '1000000', 'paid_at' => now(), 'paid_date' => today()]));
            $unpaid = JobCost::factory()->create(array_replace($base, ['unit_cost' => '1000000', 'total_cost' => '1000000']));
            $small = JobCost::factory()->create(array_replace($base, ['unit_cost' => '0.01', 'total_cost' => '0.01']));
            $controller = app(JobCostController::class);
            $html = $controller->index(Request::create('/'), $job, $service)->with('errors', new ViewErrorBag)->render();
            $previousErrors = libxml_use_internal_errors(true);
            try {
                $document = new \DOMDocument;
                $document->loadHTML($html, LIBXML_NONET);
                $xpath = new \DOMXPath($document);
                foreach ([$zero, $foreignZero, $legacyZero] as $cost) {
                    $cell = $xpath->query('//tr[td[1]/strong[normalize-space(.)="'.$cost->number.'"]]/td[6]')->item(0);
                    $this->assertNotNull($cell);
                    $this->assertStringNotContainsString('PAID', $cell->textContent);
                    $this->assertStringNotContainsString('KURANG BAYAR', $cell->textContent);
                    $status = $xpath->query('//tr[td[1]/strong[normalize-space(.)="'.$cost->number.'"]]/td[5]')->item(0);
                    $this->assertStringContainsString(config('operations.cost_statuses.final'), $status->textContent);
                }
                foreach ([[$paid, 'PAID'], [$unpaid, 'Belum PAID'], [$small, 'Belum PAID']] as [$cost, $expected]) {
                    $cell = $xpath->query('//tr[td[1]/strong[normalize-space(.)="'.$cost->number.'"]]/td[6]')->item(0);
                    $this->assertSame($expected, trim(preg_replace('/\s+/', ' ', $cell->getElementsByTagName('span')->item(0)->textContent)));
                }
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrors);
            }
            foreach ([$zero, $foreignZero, $legacyZero] as $cost) {
                $html = $controller->show($job, $cost)->with('errors', new ViewErrorBag)->render();
                $this->assertStringContainsString('tidak memerlukan pembayaran vendor', $html);
                $this->assertStringNotContainsString('Status Pembayaran Vendor:', $html);
                $this->assertStringNotContainsString('Biaya sudah PAID', $html);
                $this->assertStringNotContainsString('>Unpaid Biaya<', $html);
                $this->assertStringNotContainsString('Tandai PAID &amp; Buat Jurnal Pembayaran', $html);
            }
        });
    }

    public function test_zero_capital_still_cannot_be_marked_paid_and_no_journal_is_created(): void
    {
        $this->scenario(function ($job, $finance, $service) {
            $cost = JobCost::factory()->create(['job_id' => $job->id, 'total_cost' => '0', 'unit_cost' => '0']);
            $before = Journal::count();
            try {
                $service->markPaid($job->fresh(), $cost, ['job_version' => $job->fresh()->lock_version], $finance);
                $this->fail('Zero capital must not produce a payment journal.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('cost', $error->errors());
            }
            $this->assertSame($before, Journal::count());
            $this->assertNull($cost->fresh()->paid_at);
        });
    }

    public function test_cost_views_compile_to_valid_php(): void
    {
        foreach (['index', 'show', 'form'] as $view) {
            $php = app('blade.compiler')->compileString(file_get_contents(resource_path('views/costs/'.$view.'.blade.php')));
            token_get_all($php, TOKEN_PARSE);
            $this->assertNotEmpty($php);
        }
    }
}
