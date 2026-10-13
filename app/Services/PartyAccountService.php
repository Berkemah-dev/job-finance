<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Str;

/** Membuat COA pembantu per pihak di bawah akun piutang atau hutang induknya. */
class PartyAccountService
{
    /** @return array{receivable: ChartOfAccount, temporary: ChartOfAccount} */
    public function customerAccounts(Customer $customer): array
    {
        $receivable = $this->ensure($customer, 'receivable_account_id', 'receivable', 'Piutang Customer');
        $temporary = $this->ensure($customer, 'temporary_receivable_account_id', 'temporary_receivable', 'Piutang Temporary');

        return compact('receivable', 'temporary');
    }

    public function vendorPayable(Vendor $vendor): ChartOfAccount
    {
        $isAgent = in_array($vendor->type, ['international_agent', 'national_agent'], true);

        return $this->ensure(
            $vendor,
            'payable_account_id',
            $isAgent ? 'agent_payable' : 'vendor_payable',
            $isAgent ? 'Hutang Agent' : 'Hutang Vendor',
        );
    }

    private function ensure(Customer|Vendor $party, string $column, string $mappingKey, string $label): ChartOfAccount
    {
        $parentId = AccountMapping::where('key', $mappingKey)->value('chart_of_account_id');
        $parent = ChartOfAccount::find($parentId);
        if (! $parent) {
            throw new \RuntimeException('Mapping akun '.$mappingKey.' belum tersedia. Jalankan ChartOfAccountSeeder terlebih dahulu.');
        }
        if ($party->{$column}) {
            $account = ChartOfAccount::withTrashed()->find($party->{$column});
            if ($account && (int) $account->parent_id === (int) $parent->id) {
                $account->forceFill(['name' => $label.' — '.$party->name, 'type' => $parent->type])->save();
                return $account;
            }
        }

        $suffix = Str::upper(preg_replace('/[^A-Z0-9-]/', '', (string) ($party->code ?: $party->id)) ?: (string) $party->id);
        $code = Str::limit($parent->code.'-'.$suffix, 20, '');
        $account = ChartOfAccount::withTrashed()->firstOrCreate(
            ['code' => $code],
            ['name' => $label.' — '.$party->name, 'type' => $parent->type, 'parent_id' => $parent->id, 'level' => ($parent->level ?? 1) + 1]
        );
        $account->forceFill([
            'name' => $label.' — '.$party->name,
            'type' => $parent->type,
            'parent_id' => $parent->id,
            'level' => ($parent->level ?? 1) + 1,
        ])->save();
        $party->forceFill([$column => $account->id])->save();

        return $account;
    }
}
