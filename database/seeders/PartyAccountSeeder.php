<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Vendor;
use App\Services\PartyAccountService;
use Illuminate\Database\Seeder;

class PartyAccountSeeder extends Seeder
{
    public function run(PartyAccountService $accounts): void
    {
        Customer::withTrashed()->orderBy('id')->each(fn (Customer $customer) => $accounts->customerAccounts($customer));
        Vendor::withTrashed()->orderBy('id')->each(function (Vendor $vendor) use ($accounts) {
            $accounts->vendorPayable($vendor);
            if (in_array($vendor->type, ['international_agent', 'national_agent'], true)) {
                $accounts->agentReceivable($vendor);
            }
        });
    }
}
