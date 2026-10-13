<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Vendor;

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
        $code = $this->subledgerCode($party, $mappingKey);
        $account = $party->{$column}
            ? ChartOfAccount::withTrashed()->find($party->{$column})
            : null;

        // Gunakan COA dengan kode pembantu yang sudah ada bila seeder dijalankan ulang.
        $codedAccount = ChartOfAccount::withTrashed()->where('code', $code)->first();
        if ($codedAccount && (! $account || $codedAccount->id !== $account->id)) {
            $account = $codedAccount;
        }

        if (! $account) {
            $account = new ChartOfAccount(['code' => $code]);
        }

        // Akun lama yang memakai kode berbasis nama pihak tetap memakai ID yang sama agar
        // jurnal historis tidak terputus, lalu dinormalisasi ke format numerik baru.
        $sameCode = ChartOfAccount::withTrashed()->where('code', $code);
        if ($account->exists) {
            $sameCode->where('id', '!=', $account->id);
        }
        $canUseCode = ! $sameCode->exists();
        $account->forceFill([
            'code' => $canUseCode ? $code : $account->code,
            'name' => $label.' — '.$party->name,
            'type' => $parent->type,
            'parent_id' => $parent->id,
            'level' => ($parent->level ?? 1) + 1,
        ])->save();
        $party->forceFill([$column => $account->id])->save();

        return $account;
    }

    private function subledgerCode(Customer|Vendor $party, string $mappingKey): string
    {
        $prefix = match ($mappingKey) {
            'receivable' => '1200',
            'temporary_receivable' => '1201',
            'vendor_payable' => '2100',
            'agent_payable' => '2200',
            default => throw new \InvalidArgumentException('Mapping COA pembantu tidak dikenali.'),
        };

        return $prefix.str_pad((string) $party->id, 3, '0', STR_PAD_LEFT);
    }
}
