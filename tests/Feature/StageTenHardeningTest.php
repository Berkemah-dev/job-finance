<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobClosingSnapshot;
use App\Models\Journal;
use App\Models\JournalAdjustment;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageTenHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@jobfinance.test')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_create_and_update_user_with_role_and_audit(): void
    {
        $finance = Role::where('name', 'finance')->firstOrFail();
        $this->get('/users/create')->assertOk();
        $this->post('/users', ['name' => 'Finance Dua', 'email' => 'finance2@example.test', 'role_id' => $finance->id, 'password' => 'FinanceDemo2026', 'password_confirmation' => 'FinanceDemo2026'])->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('email', 'finance2@example.test')->firstOrFail();
        $this->assertTrue($user->hasPermission('payments.manage'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.created', 'user_id' => $this->admin->id]);
        $operational = Role::where('name', 'operational')->firstOrFail();
        $oldPassword = $user->password;
        $this->put('/users/'.$user->id, ['lock_version' => 0, 'name' => 'Operasional Dua', 'email' => 'ops2@example.test', 'role_id' => $operational->id, 'password' => '', 'password_confirmation' => ''])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('ops2@example.test', $user->email);
        $this->assertSame($oldPassword, $user->password);
        $this->assertSame(1, $user->lock_version);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.updated']);
    }

    public function test_user_validation_stale_update_and_last_admin_protection(): void
    {
        $finance = Role::where('name', 'finance')->firstOrFail();
        $this->post('/users', ['name' => '', 'email' => 'bad', 'role_id' => 999, 'password' => 'short', 'password_confirmation' => 'different'])->assertSessionHasErrors(['name', 'email', 'role_id', 'password']);
        $this->put('/users/'.$this->admin->id, ['lock_version' => 99, 'name' => $this->admin->name, 'email' => $this->admin->email, 'role_id' => $finance->id])->assertSessionHasErrors('lock_version');
        $this->put('/users/'.$this->admin->id, ['lock_version' => 0, 'name' => $this->admin->name, 'email' => $this->admin->email, 'role_id' => $finance->id])->assertSessionHasErrors('role_id');
        $this->assertSame('super-admin', $this->admin->fresh()->role->name);
    }

    public function test_non_admin_cannot_open_or_submit_user_management(): void
    {
        $this->actingAs(User::where('email', 'finance@jobfinance.test')->firstOrFail());
        $this->get('/users/create')->assertForbidden();
        $this->get('/users/'.$this->admin->id.'/edit')->assertForbidden();
        $this->post('/users', [])->assertForbidden();
        $this->put('/users/'.$this->admin->id, [])->assertForbidden();
    }

    public function test_report_and_journal_filters_reject_invalid_dates_and_types(): void
    {
        $this->get('/reports/income-statement?from='.today()->addDay()->toDateString())->assertSessionHasErrors('from');
        $this->get('/reports/cash-flow?from=not-a-date')->assertSessionHasErrors('from');
        $this->get('/journals?type=destroyed')->assertSessionHasErrors('type');
        $this->get('/journals?from='.today()->toDateString().'&to='.today()->subDay()->toDateString())->assertSessionHasErrors('to');
    }

    public function test_stage_six_to_nine_models_have_working_factories(): void
    {
        $snapshot = JobClosingSnapshot::factory()->create();
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        Payment::factory()->create(['invoice_id' => $invoice->id]);
        $source = JournalAdjustment::factory()->create();
        $journal = Journal::factory()->create(['source_id' => $source->id]);
        JournalEntry::factory()->create(['journal_id' => $journal->id]);
        $this->assertDatabaseHas('job_closing_snapshots', ['id' => $snapshot->id]);
        $this->assertCount(1, $invoice->items);
        $this->assertCount(1, $invoice->payments);
        $this->assertCount(1, $journal->entries);
    }
}
