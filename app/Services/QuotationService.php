<?php

namespace App\Services;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\TruckingPrice;
use App\Models\User;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class QuotationService
{
    public function __construct(private DocumentNumberService $numbers, private MasterDataService $master, private PricingService $pricing) {}

    public function save(?Quotation $quotation, array $data, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $actor) {
            $new = $quotation === null;
            if ($new) {
                Gate::forUser($actor)->authorize('create', Quotation::class);
                $quotation = new Quotation;
                $quotation->number = $this->numbers->next('quo');
                $quotation->status = QuotationStatus::Draft;
                $quotation->created_by = $actor->id;
            } else {
                $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
                Gate::forUser($actor)->authorize('update', $quotation);
                $this->master->checkVersion($quotation, $data);
            }
            $customer = $this->activeCustomer($data['customer_id']);
            $calculated = $this->calculate($data['items'], $data['quotation_date'] ?? now());
            $quotation->fill(Arr::only($data, ['customer_id', 'subject', 'quotation_date', 'valid_until', 'notes']));
            $quotation->customer_snapshot = $customer->only(['code', 'name', 'contact_name', 'email', 'phone', 'address', 'tax_number']);
            $quotation->forceFill($calculated['totals']);
            $quotation->updated_by = $actor->id;
            $quotation->lock_version = $new ? 0 : $quotation->lock_version + 1;
            $quotation->save();
            $quotation->items()->delete();
            $quotation->items()->createMany($calculated['items']);
            $this->master->log($actor, $new ? 'quotation.created' : 'quotation.updated', ($new ? 'Membuat ' : 'Memperbarui ').$quotation->number);

            return $quotation;
        }, 3);
    }

    public function transition(Quotation $quotation, string $action, array $data, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $action, $data, $actor) {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
            Gate::forUser($actor)->authorize($action, $quotation);
            $this->master->checkVersion($quotation, $data);
            if ($action !== 'reject') {
                $this->activeCustomer($quotation->customer_id);
            }
            if ($action === 'submit') {
                if (! $quotation->items()->exists()) {
                    throw ValidationException::withMessages(['items' => 'Quotation harus memiliki item.']);
                }
                $quotation->status = QuotationStatus::Submitted;
                $quotation->submitted_by = $actor->id;
                $quotation->submitted_at = now();
            } elseif ($action === 'approve') {
                $quotation->status = QuotationStatus::Approved;
                $quotation->approved_by = $actor->id;
                $quotation->approved_at = now();
            } elseif ($action === 'reject') {
                if (trim($data['reason'] ?? '') === '') {
                    throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
                }
                $quotation->status = QuotationStatus::Rejected;
                $quotation->rejected_by = $actor->id;
                $quotation->rejected_at = now();
                $quotation->rejection_reason = $data['reason'];
            } else {
                throw new \InvalidArgumentException('Unknown transition');
            }
            $quotation->updated_by = $actor->id;
            $quotation->lock_version++;
            $quotation->save();
            $this->master->log($actor, 'quotation.'.$action, $quotation->number.' → '.$quotation->status->label());

            return $quotation;
        }, 3);
    }

    public function convert(Quotation $quotation, array $data, User $actor): Job
    {
        return DB::transaction(function () use ($quotation, $data, $actor) {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
            Gate::forUser($actor)->authorize('convert', $quotation);
            $this->master->checkVersion($quotation, $data);
            $this->activeCustomer($quotation->customer_id);
            if ($quotation->job()->exists()) {
                throw ValidationException::withMessages(['quotation' => 'Quotation sudah dikonversi.']);
            }
            $quotation->load('items');
            $snapshot = [
                'number' => $quotation->number, 'customer' => $quotation->customer_snapshot, 'subject' => $quotation->subject,
                'quotation_date' => $quotation->quotation_date->format('Y-m-d'), 'valid_until' => $quotation->valid_until->format('Y-m-d'), 'notes' => $quotation->notes,
                'totals' => $quotation->only(['total_temporary', 'total_provision_cost', 'total_provision_sell', 'subtotal', 'profit', 'margin']),
                'items' => $quotation->items->map(fn ($item) => $item->only(['description', 'type', 'unit', 'quantity', 'unit_cost', 'unit_price', 'total_cost', 'total_price']))->all(),
            ];
            $job = Job::create(['number' => $this->numbers->next('job'), 'quotation_id' => $quotation->id, 'customer_id' => $quotation->customer_id,
                'subject' => $quotation->subject, 'status' => 'draft', 'job_date' => now()->toDateString(), 'quotation_snapshot' => $snapshot, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
            $quotation->status = QuotationStatus::Converted;
            $quotation->converted_by = $actor->id;
            $quotation->converted_at = now();
            $quotation->updated_by = $actor->id;
            $quotation->lock_version++;
            $quotation->save();
            $this->master->log($actor, 'quotation.converted', $quotation->number.' → '.$job->number);

            return $job;
        }, 3);
    }

    private function activeCustomer(int|string $id): Customer
    {
        $customer = Customer::whereKey($id)->lockForUpdate()->first();
        if (! $customer) {
            throw ValidationException::withMessages(['customer_id' => 'Customer sudah diarsipkan atau tidak tersedia. Pilih customer aktif.']);
        }

        return $customer;
    }

    public function calculate(array $rows, string|Carbon|null $date = null): array
    {
        $temporary = Money::decimal('0');
        $cost = Money::decimal('0');
        $sell = Money::decimal('0');
        $items = [];
        foreach (array_values($rows) as $i => $row) {
            $row = $this->normalizePricingRow($row, $i, $date);
            $quantity = Money::decimal($row['quantity']);
            $unitCost = Money::decimal($row['unit_cost']);
            $unitPrice = Money::decimal($row['unit_price']);
            if ($row['type'] === 'temporary' && ! $unitCost->isEqualTo($unitPrice)) {
                throw ValidationException::withMessages(['items.'.$i.'.unit_price' => 'Temporary ditagihkan sebesar biaya: nilai jual harus sama dengan modal.']);
            }
            $totalCost = $quantity->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp);
            $totalPrice = $quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp);
            $items[] = array_merge($row, ['position' => $i + 1, 'quantity' => (string) $quantity, 'unit_cost' => (string) $unitCost, 'unit_price' => (string) $unitPrice, 'total_cost' => Money::checked($totalCost), 'total_price' => Money::checked($totalPrice)]);
            if ($row['type'] === 'temporary') {
                $temporary = $temporary->plus($totalCost);
            } else {
                $cost = $cost->plus($totalCost);
                $sell = $sell->plus($totalPrice);
            }
        }
        $profit = $sell->minus($cost);
        $margin = $sell->isZero() ? Money::decimal('0') : $profit->multipliedBy('100')->dividedBy($sell, 2, RoundingMode::HalfUp);

        return ['items' => $items, 'totals' => ['total_temporary' => Money::checked($temporary), 'total_provision_cost' => Money::checked($cost),
            'total_provision_sell' => Money::checked($sell), 'subtotal' => Money::checked($temporary->plus($sell)), 'profit' => Money::checked($profit), 'margin' => Money::checked($margin)]];
    }

    /**
     * Normalisasi baris item: mata uang + kurs (weekly pricing sebagai sumber kebenaran),
     * lalu bila bersumber tarif trucking, modal diambil dari master tarif, bukan dari input klien.
     */
    private function normalizePricingRow(array $row, int $index, string|Carbon|null $date): array
    {
        $currency = (string) ($row['currency'] ?? 'IDR');
        if ($currency === '' || ! in_array($currency, array_keys(config('operations.currencies')), true)) {
            throw ValidationException::withMessages(['items.'.$index.'.currency' => 'Mata uang tidak valid.']);
        }
        $containerType = isset($row['container_type']) && $row['container_type'] !== ''
            ? (string) $row['container_type']
            : null;
        if ($containerType !== null && ! in_array($containerType, array_keys(config('operations.container_types')), true)) {
            throw ValidationException::withMessages(['items.'.$index.'.container_type' => 'Jenis kontainer tidak valid.']);
        }
        $row['currency'] = $currency;
        $row['overweight'] = filter_var($row['overweight'] ?? false, FILTER_VALIDATE_BOOL);
        $row['container_type'] = $containerType;
        $row['gross_weight'] = $this->nullableNumber($row['gross_weight'] ?? null);
        $row['volume'] = $this->nullableNumber($row['volume'] ?? null);
        $row['pricing_snapshot'] = null;

        if (($row['pricing_source'] ?? '') === 'trucking') {
            $suggestion = $this->truckingSuggestion($row, $date);
            if (! $suggestion['found']) {
                throw ValidationException::withMessages(['items.'.$index.'.pricing' => 'Tarif trucking tidak ditemukan atau sudah tidak aktif.']);
            }
            $row['unit_cost'] = $suggestion['unit_cost'];
            $row['pricing_source'] = 'trucking';
            $row['pricing_id'] = $suggestion['pricing_id'];
            $row['currency'] = $suggestion['currency'];
            $row['exchange_rate'] = $suggestion['exchange_rate'];
            $row['container_type'] = $suggestion['container_type'];
            $row['overweight'] = $suggestion['overweight'];
            $row['pricing_snapshot'] = $suggestion['snapshot'];
        } else {
            $row['pricing_source'] = 'manual';
            $row['pricing_id'] = null;
            $row['exchange_rate'] = $this->pricing->convertedRate($currency, $date);
        }

        return $row;
    }

    private function truckingSuggestion(array $row, string|Carbon|null $date): array
    {
        $price = null;
        if (! empty($row['pricing_id'])) {
            $price = TruckingPrice::whereKey((int) $row['pricing_id'])->where('is_active', true)->first();
        }
        if (! $price) {
            $price = $this->pricing->findTruckingPrice(
                (string) ($row['port_origin'] ?? ''),
                (string) ($row['destination'] ?? ''),
                (string) ($row['container_type'] ?? 'lcl'),
                (bool) ($row['overweight'] ?? false),
                ! empty($row['vendor_id']) ? (int) $row['vendor_id'] : null,
                $date
            );
        }

        return $price
            ? $this->pricing->suggestTrucking($price->port_origin, $price->destination, $price->container_type, (bool) $price->overweight, $price->vendor_id, $date)
            : ['found' => false];
    }

    private function nullableNumber(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
