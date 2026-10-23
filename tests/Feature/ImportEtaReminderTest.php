<?php

namespace Tests\Feature;

use App\Http\Controllers\NotificationController;
use App\Models\Job;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportEtaReminderTest extends TestCase
{
    public function test_cs_receives_import_eta_reminders_with_correct_date_and_status_boundaries(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.eta_reminder_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('eta_reminder_test');
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-23 20:00:00'));
        try {
            Artisan::call('migrate', ['--database' => 'eta_reminder_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $cs = User::where('email', 'customer-service@jobfinance.test')->firstOrFail();
            $this->actingAs($cs);
            $this->assertTrue($cs->hasPermission('jobs.view'));
            $make = fn ($service, $days, $status = 'open') => Job::factory()->open()->create([
                'service_type' => $service, 'status' => $status,
                'eta' => $days === null ? null : today()->addDays($days)->toDateString(),
            ]);
            $sea = $make('imp_sea', 0);
            $air = $make('imp_air', 3);
            $seven = $make('imp_sea', 7);
            $excluded = collect([
                $make('exp_sea', 1), $make('domestic', 1), $make('imp_sea', -1),
                $make('imp_sea', 8), $make('imp_sea', 1, 'closed'),
                $make('imp_sea', 1, 'draft'), $make('imp_sea', null),
            ]);
            $summary = app(DashboardService::class)->summary($cs, 'customer-service');
            $this->assertEqualsCanonicalizing([$sea->id, $air->id], $summary['arrivalSoon']->modelKeys());
            $request = Request::create('/notifications');
            $request->setUserResolver(fn () => $cs);
            $notifications = app(NotificationController::class)->index($request)->getData()['notifications'];
            $eta = $notifications->where('title', 'ETA Import mendekat')->values();
            $this->assertCount(3, $eta);
            $this->assertEqualsCanonicalizing(
                [route('jobs.show', $sea), route('jobs.show', $air), route('jobs.show', $seven)],
                $eta->pluck('url')->all()
            );
            $this->assertStringContainsString('hari ini', $eta[0]['message']);
            $this->assertSame('warning', $eta[0]['type']);
            $this->assertSame('ETA Import mendekat', $notifications->first()['title']);
            foreach ($excluded as $job) {
                $this->assertFalse($eta->contains('url', route('jobs.show', $job)));
            }
            $this->assertTrue(Job::approachingEta(3)->where('service_type', 'exp_sea')->exists());
            // Query-time reminders are rebuilt from jobs, with no separate seeded notification records.
            $sea->update(['eta' => today()->addDays(9)]);
            $this->assertFalse(Job::approachingEta(7, true)->whereKey($sea->id)->exists());
            $html = view('dashboard.customer-service', $summary)
                ->with('errors', new \Illuminate\Support\ViewErrorBag)->render();
            $this->assertStringContainsString('Pengingat ETA Import', $html);
            $this->assertStringContainsString(route('notifications.index'), $html);
        } finally {
            $this->travelBack();
            DB::setDefaultConnection($previous);
            DB::purge('eta_reminder_test');
        }
    }
}
