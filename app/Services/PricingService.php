<?php

namespace App\Services;

use App\Models\TruckingPrice;
use App\Models\User;
use App\Models\WeeklyPricing;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(private MasterDataService $master) {}

    public function save(Model $model, array $data, User $actor): Model
    {
        return DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize('pricing.manage');
            $new = ! $model->exists;
            $before = null;
            if (! $new) {
                $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
                $this->master->checkVersion($model, $data);
                $before = $model->only($this->editableKeys($model));
            }
            $model->fill($data);
            $model->created_by = $new ? $actor->id : $model->created_by;
            $model->updated_by = $actor->id;
            $model->lock_version = $new ? 0 : $model->lock_version + 1;
            $model->save();
            $this->master->log($actor, $this->actionFor($model, $new ? 'created' : 'updated'), $this->label($model), [
                'module' => $model instanceof WeeklyPricing ? 'weekly_pricing' : 'trucking_price',
                'record_id' => $model->id,
                'before' => $before,
                'after' => $model->only($this->editableKeys($model)),
            ]);

            return $model;
        }, 3);
    }

    public function toggle(Model $model, array $data, User $actor): void
    {
        DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize('pricing.manage');
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
            $this->master->checkVersion($model, $data);
            $model->is_active = ! $model->is_active;
            $model->updated_by = $actor->id;
            $model->lock_version++;
            $model->save();
            $this->master->log($actor, $this->actionFor($model, $model->is_active ? 'activated' : 'deactivated'), $this->label($model), [
                'module' => $model instanceof WeeklyPricing ? 'weekly_pricing' : 'trucking_price',
                'record_id' => $model->id,
                'after' => ['is_active' => $model->is_active],
            ]);
        }, 3);
    }

    public function archive(Model $model, array $data, User $actor): void
    {
        DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize('pricing.manage');
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
            $this->master->checkVersion($model, $data);
            $model->updated_by = $actor->id;
            $model->lock_version++;
            $model->save();
            $model->delete();
            $this->master->log($actor, $this->actionFor($model, 'deleted'), $this->label($model), [
                'module' => $model instanceof WeeklyPricing ? 'weekly_pricing' : 'trucking_price',
                'record_id' => $model->id,
                'after' => ['deleted' => true],
            ]);
        }, 3);
    }

    public function activeWeeklyRate(string $currency, ?string $service = null, string|Carbon|null $date = null): ?WeeklyPricing
    {
        $date = $date ? Carbon::parse($date) : today();

        return WeeklyPricing::where('currency', $currency)->where('is_active', true)
            ->where('effective_date', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $date->toDateString()))
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->when($service, fn ($q, $s) => $q->where(fn ($q) => $q->where('service', $s)->orWhereNull('service')))
            ->first();
    }

    public function findTruckingPrice(string $portOrigin, string $destination, string $containerType, bool $overweight = false, ?int $vendorId = null, string|Carbon|null $date = null): ?TruckingPrice
    {
        $date = $date ? Carbon::parse($date) : today();

        return TruckingPrice::where('port_origin', $portOrigin)->where('destination', $destination)
            ->where('container_type', $containerType)->where('overweight', $overweight)
            ->where('is_active', true)->where('effective_date', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $date->toDateString()))
            ->when($vendorId, fn ($q, $id) => $q->where('vendor_id', $id))
            ->orderByDesc('effective_date')->orderByDesc('id')->first();
    }

    /**
     * Kurs resmi untuk mata uang pada tanggal tertentu (dari weekly pricing aktif).
     * IDR selalu 1; mata uang tanpa kurs mingguan aktif ditolak agar nilai selalu terdokumentasi.
     */
    public function convertedRate(string $currency, string|Carbon|null $date = null): string
    {
        if ($currency === 'IDR') {
            return '1.00';
        }
        $weekly = $this->activeWeeklyRate($currency, null, $date);
        if (! $weekly) {
            throw ValidationException::withMessages(['items.*.currency' => 'Tidak ada weekly pricing aktif untuk '.$currency.' pada tanggal tersebut.']);
        }

        return (string) $weekly->exchange_rate;
    }

    /**
     * Usulan tarif trucking untuk quotation: harga modal (IDR terkurs) + jejak sumber tarif.
     */
    public function suggestTrucking(string $portOrigin, string $destination, string $containerType, bool $overweight = false, ?int $vendorId = null, string|Carbon|null $date = null): array
    {
        $price = $this->findTruckingPrice($portOrigin, $destination, $containerType, $overweight, $vendorId, $date);
        if (! $price) {
            return ['found' => false];
        }
        $rate = $this->convertedRate($price->currency, $date);
        $unitCost = Money::decimal($price->price)->multipliedBy($rate)->toScale(2, RoundingMode::HalfUp);
        $snapshot = [
            'source' => 'trucking', 'port_origin' => $price->port_origin, 'destination' => $price->destination,
            'container_type' => $price->container_type, 'overweight' => (bool) $price->overweight,
            'vendor_id' => $price->vendor_id, 'vendor_name' => $price->vendor?->name,
            'price' => Money::checked(Money::decimal($price->price)), 'currency' => $price->currency,
            'effective_date' => $price->effective_date->format('Y-m-d'), 'exchange_rate' => $rate,
        ];

        return [
            'found' => true, 'pricing_id' => $price->id, 'unit_cost' => Money::checked($unitCost),
            'currency' => $price->currency, 'exchange_rate' => $rate, 'container_type' => $price->container_type,
            'overweight' => (bool) $price->overweight, 'port_origin' => $price->port_origin, 'destination' => $price->destination,
            'vendor_name' => $price->vendor?->name, 'effective_date' => $price->effective_date->format('Y-m-d'), 'snapshot' => $snapshot,
        ];
    }

    private function editableKeys(Model $model): array
    {
        return $model instanceof WeeklyPricing
            ? ['week', 'effective_date', 'effective_until', 'currency', 'exchange_rate', 'service', 'notes', 'is_active']
            : ['port_origin', 'destination', 'overweight', 'container_type', 'vendor_id', 'price', 'currency', 'effective_date', 'effective_until', 'is_active'];
    }

    private function actionFor(Model $model, string $suffix): string
    {
        return ($model instanceof WeeklyPricing ? 'weekly_pricing.' : 'trucking_price.').$suffix;
    }

    private function label(Model $model): string
    {
        return $model instanceof WeeklyPricing
            ? 'Kurs '.$model->currency.' '.$model->week.' · '.($model->effective_date?->format('d M Y') ?? $model->effective_date)
            : $model->port_origin.' → '.$model->destination.' · '.strtoupper($model->container_type);
    }
}
