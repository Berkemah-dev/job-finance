<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuotationPdfTotalTest extends TestCase
{
    private function pdfTextStreams(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);

        return implode("\n", array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $matches[1]));
    }

    public function test_saved_checkbox_controls_total_in_preview_download_and_print_without_changing_amounts(): void
    {
        $previous = DB::getDefaultConnection();
        $this->app->instance('env', 'testing');
        config(['database.connections.quotation_total_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_total_test');
        try {
            Artisan::call('migrate', ['--database' => 'quotation_total_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) app($seeder)->run();
            $actor = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
            $this->actingAs($actor);
            $customer = Customer::factory()->create(['approval_status' => 'approved', 'name' => 'PT CUSTOMER CONTOH']);
            $data = [
                'customer_id' => $customer->id, 'sales_id' => $actor->id, 'subject' => 'Contoh Penawaran',
                'quotation_date' => today()->toDateString(), 'valid_until' => today()->addDays(30)->toDateString(),
                'service_type' => 'imp_sea', 'currency' => 'IDR', 'exchange_rate' => '1',
                'discount' => '100000', 'tax_rate' => '10',
                'items' => [
                    ['description' => 'TRUCKING', 'type' => 'provision', 'unit' => 'Shipment', 'quantity' => '1',
                        'unit_cost' => '1000000', 'unit_price' => '1500000', 'currency' => 'IDR', 'exchange_rate' => '1'],
                    ['description' => 'CUSTOMS CLEARANCE SPPB', 'type' => 'provision', 'unit' => 'Shipment', 'quantity' => '1',
                        'unit_cost' => '0', 'unit_price' => '50', 'currency' => 'USD', 'exchange_rate' => '10000',
                        'cost_currency' => 'IDR', 'cost_exchange_rate' => '1'],
                ],
            ];
            $sequenceBefore = DB::table('document_sequences')->get()->toArray();
            $this->post(route('quotations.preview-create'), $data)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertSame(0, Quotation::count());
            $this->assertSame($sequenceBefore, DB::table('document_sequences')->get()->toArray());
            $this->post(route('quotations.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
            $quotation = Quotation::latest('id')->firstOrFail();
            $this->assertFalse($quotation->show_total_on_pdf);
            $this->assertSame('2000000.00', $quotation->subtotal);
            $this->assertSame('2090000.00', $quotation->grand_total);
            $originalAmounts = $quotation->only(['subtotal', 'grand_total', 'discount', 'tax_amount', 'profit']);

            $render = fn () => view('documents.pdf.quotation', ['quotation' => $quotation->fresh(['items', 'creator', 'sales', 'customer'])])->render();
            $this->assertStringNotContainsString('TOTAL PENAWARAN (IDR):', $render());
            $this->put(route('quotations.update', $quotation), $data + ['lock_version' => $quotation->lock_version, 'show_total_on_pdf' => '1'])
                ->assertSessionHasNoErrors()->assertRedirect();
            $quotation->refresh();
            $this->assertTrue($quotation->show_total_on_pdf);
            $this->assertSame($originalAmounts, $quotation->only(array_keys($originalAmounts)));
            $visible = $render();
            $this->assertStringContainsString('TOTAL PENAWARAN (IDR):', $visible);
            $this->assertStringContainsString('2,090,000.00', $visible);
            $this->assertSame($visible, view('quotations.pdf', ['quotation' => $quotation->fresh(['items', 'creator', 'sales', 'customer'])])->render());
            $this->get(route('quotations.edit', $quotation))->assertOk()
                ->assertSee('Tampilkan Total Penawaran di PDF')->assertSee('name="show_total_on_pdf" value="1" style="width:auto;" checked', false);
            $this->get(route('quotations.preview', $quotation))->assertOk()->assertSee('/pdf', false);
            $on = $this->get(route('quotations.pdf', ['quotation' => $quotation, 'mode' => 'download']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get(route('quotations.print', $quotation))->assertOk()->assertHeader('Content-Type', 'application/pdf');

            $this->put(route('quotations.update', $quotation), $data + ['lock_version' => $quotation->lock_version, 'show_total_on_pdf' => '2'])->assertSessionHasErrors('show_total_on_pdf');
            $this->assertTrue($quotation->fresh()->show_total_on_pdf);
            $this->put(route('quotations.update', $quotation), $data + ['lock_version' => $quotation->lock_version])
                ->assertSessionHasNoErrors()->assertRedirect();
            $quotation->refresh();
            $this->assertFalse($quotation->show_total_on_pdf);
            $this->assertSame($originalAmounts, $quotation->only(array_keys($originalAmounts)));
            $this->assertStringNotContainsString('TOTAL PENAWARAN (IDR):', $render());
            $this->get(route('quotations.edit', $quotation))->assertOk()
                ->assertDontSee('name="show_total_on_pdf" value="1" style="width:auto;" checked', false);
            $off = $this->get(route('quotations.pdf', $quotation))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get(route('quotations.edit', $quotation))->assertOk()->assertSee('Preview Cetakan PDF')
                ->assertSee('formaction="'.route('quotations.preview-form', $quotation).'" formtarget="_blank"', false);
            $unsaved = $data;
            $unsaved['items'][0]['unit_price'] = '2000000';
            $unsaved['show_total_on_pdf'] = '1';
            $unsaved['lock_version'] = $quotation->lock_version;
            $beforePreview = $quotation->fresh()->toArray();
            $itemsBeforePreview = $quotation->items()->get()->toArray();
            $draftResponse = $this->post(route('quotations.preview-form', $quotation), $unsaved + ['_method' => 'PUT'])->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'inline; filename="Preview_Quotation.pdf"');
            $this->assertStringContainsString('TOTAL PENAWARAN', $this->pdfTextStreams($draftResponse->getContent()));
            $this->assertStringContainsString('2,640,000.00', $this->pdfTextStreams($draftResponse->getContent()));
            $this->assertSame($beforePreview, $quotation->fresh()->toArray());
            $this->assertSame($itemsBeforePreview, $quotation->items()->get()->toArray());
            $draft = app(\App\Services\QuotationService::class)->previewForm($quotation, $unsaved, $actor);
            $this->assertSame('2640000.00', $draft->grand_total);
            $draftHtml = view('documents.pdf.quotation', ['quotation' => $draft])->render();
            $this->assertStringContainsString('TOTAL PENAWARAN (IDR):', $draftHtml);
            $this->assertStringContainsString('2,640,000.00', $draftHtml);
            $this->assertFalse($quotation->fresh()->show_total_on_pdf);
            $unsaved['show_total_on_pdf'] = '0';
            $hiddenResponse = $this->post(route('quotations.preview-form', $quotation), $unsaved + ['_method' => 'PUT'])->assertOk();
            $this->assertStringNotContainsString('TOTAL PENAWARAN', $this->pdfTextStreams($hiddenResponse->getContent()));
            $hiddenDraft = app(\App\Services\QuotationService::class)->previewForm($quotation, $unsaved, $actor);
            $this->assertStringNotContainsString('TOTAL PENAWARAN (IDR):', view('documents.pdf.quotation', ['quotation' => $hiddenDraft])->render());
            if (getenv('QUOTATION_TOTAL_PREVIEW')) {
                \Illuminate\Support\Facades\File::ensureDirectoryExists(base_path('tmp/pdfs'));
                file_put_contents(base_path('tmp/pdfs/quotation-total-visible.pdf'), $on->getContent());
                file_put_contents(base_path('tmp/pdfs/quotation-total-hidden.pdf'), $off->getContent());
            }

            // Print preferences remain editable after conversion without unlocking financial fields.
            $quotation->forceFill(['status' => \App\Enums\QuotationStatus::Converted])->save();
            $job = \App\Models\Job::factory()->open()->create([
                'quotation_id' => $quotation->id, 'customer_id' => $customer->id,
                'quotation_snapshot' => ['subject' => 'Original snapshot'],
            ]);
            $jobBefore = $job->fresh()->toArray();
            $itemsBefore = $quotation->items()->get()->toArray();
            $admin = User::where('email', 'admin@jobfinance.test')->firstOrFail();
            $this->actingAs($admin);
            $this->get(route('quotations.edit', $quotation))->assertRedirect(route('quotations.show', $quotation));
            $this->post(route('quotations.preview-form', $quotation), $unsaved + ['_method' => 'PUT'])->assertForbidden();
            $this->get(route('quotations.show', $quotation))->assertOk()->assertSee('Simpan Pengaturan PDF');
            $this->put(route('quotations.update', $quotation), $data + ['lock_version' => $quotation->lock_version])->assertForbidden();
            $settings = ['lock_version' => $quotation->lock_version, 'show_total_on_pdf' => '1'];
            $this->patch(route('quotations.pdf-settings', $quotation), $settings + ['subject' => 'Must not change'])
                ->assertSessionHasNoErrors()->assertRedirect(route('quotations.show', $quotation));
            $quotation->refresh();
            $this->assertTrue($quotation->show_total_on_pdf);
            $this->assertSame(\App\Enums\QuotationStatus::Converted, $quotation->status);
            $this->assertSame('Contoh Penawaran', $quotation->subject);
            $this->assertSame($originalAmounts, $quotation->only(array_keys($originalAmounts)));
            $this->assertSame($jobBefore, $job->fresh()->toArray());
            $this->assertSame($itemsBefore, $quotation->items()->get()->toArray());
            $this->assertSame(0, \App\Models\Journal::count());
            $this->assertStringContainsString('TOTAL PENAWARAN (IDR):', $render());
            $this->patch(route('quotations.pdf-settings', $quotation), $settings)->assertSessionHasErrors('lock_version');
            $this->patch(route('quotations.pdf-settings', $quotation), ['lock_version' => $quotation->lock_version, 'show_total_on_pdf' => '0'])
                ->assertSessionHasNoErrors()->assertRedirect();
            $this->assertFalse($quotation->fresh()->show_total_on_pdf);
            foreach (['finance@jobfinance.test', 'sales@jobfinance.test'] as $email) {
                $this->actingAs(User::where('email', $email)->firstOrFail());
                $this->patch(route('quotations.pdf-settings', $quotation), ['lock_version' => $quotation->fresh()->lock_version, 'show_total_on_pdf' => '1'])->assertForbidden();
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('quotation_total_test');
        }
    }
}
