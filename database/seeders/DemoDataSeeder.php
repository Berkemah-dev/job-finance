<?php

namespace Database\Seeders;

use App\Models\BookingConfirmation;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Job;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\ShippingInstruction;
use App\Models\Tps;
use App\Models\TruckingPrice;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\WeeklyPricing;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use App\Services\JobService;
use App\Services\PaymentService;
use App\Services\QuotationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $users = $this->users();
            $customers = $this->customers();
            $vendors = $this->vendors();
            $this->pricing($vendors['trucking']);
            $this->tps();

            $jobs = $this->jobs($customers, $users);
            $this->operationalDocuments($jobs, $customers, $users['operation']);
            $this->reimbursements($jobs, $vendors, $users);
        }, 3);
    }

    private function users(): array
    {
        $result = [];
        $map = [
            'admin' => ['Super Admin Demo', 'super-admin'],
            'sales' => ['Sales Demo', 'sales'],
            'sales_manager' => ['Sales Manager Demo', 'sales-manager'],
            'operation' => ['Operation Demo', 'operational'],
            'finance' => ['Finance Demo', 'finance'],
        ];

        foreach ($map as $key => [$name, $roleName]) {
            $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucwords(str_replace('-', ' ', $roleName))]);
            $user = User::firstOrCreate(
                ['email' => $key.'@jobfinance.test'],
                ['name' => $name, 'password' => 'JobFinance!2026']
            );
            if ($user->role_id !== $role->id) {
                $user->role()->associate($role);
                $user->save();
            }
            $result[$key] = $user->fresh('role');
        }

        return $result;
    }

    private function customers(): array
    {
        $rows = [
            'sea' => ['DEMO-SEA', 'PT Nusantara Logistik', 'Budi Santoso', 'finance@nusantara.test', 'Jakarta Utara', 'net_30'],
            'air' => ['DEMO-AIR', 'PT Garuda Elektronik', 'Maya Wijaya', 'accounting@garuda-elektronik.test', 'Tangerang', 'net_14'],
            'import' => ['DEMO-IMP', 'PT Prima Retail Indonesia', 'Anton Pradipta', 'ap@primaretail.test', 'Bekasi', 'net_30'],
            'domestic' => ['DEMO-DOM', 'PT Sinar Distribusi', 'Rina Amelia', 'finance@sinar-distribusi.test', 'Bandung', 'net_7'],
        ];

        $result = [];
        foreach ($rows as $key => [$code, $name, $pic, $email, $city, $terms]) {
            $customer = Customer::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'contact_name' => $pic,
                    'email' => $email,
                    'phone' => '021-555-'.str_pad((string) (array_search($key, array_keys($rows), true) + 110), 4, '0', STR_PAD_LEFT),
                    'address' => 'Jl. Demo Raya No. '.(array_search($key, array_keys($rows), true) + 1).', '.$city,
                    'authorizer_name' => $pic,
                    'authorizer_title' => 'Direktur Operasional',
                    'tax_number' => '0'.random_int(100000000000000, 999999999999999),
                    'default_payment_terms' => $terms,
                ]
            );
            $this->contacts($customer);
            $result[$key] = $customer;
        }

        return $result;
    }

    private function contacts(Customer $customer): void
    {
        foreach ([
            ['shipper', $customer->name.' Shipper', 'Gudang '.$customer->name],
            ['consignee', $customer->name.' Consignee', 'Penerima '.$customer->name],
        ] as [$type, $name, $company]) {
            CustomerContact::updateOrCreate(
                ['customer_id' => $customer->id, 'type' => $type, 'name' => $name],
                ['company' => $company, 'email' => strtolower(str_replace(' ', '.', $name)).'@demo.test', 'phone' => '0812-555-0101', 'address' => $customer->address, 'country' => 'Indonesia', 'is_active' => true]
            );
        }
    }

    private function vendors(): array
    {
        $rows = [
            'trucking' => ['VND-DEMO-TRK', 'PT Demo Trucking Indonesia', 'trucking', 'Jakarta', 'BCA', 'Demo Trucking'],
            'shipping' => ['VND-DEMO-SL', 'Demo Ocean Line', 'shipping_line', 'Singapore', 'OCBC', 'Demo Ocean Line'],
            'agent' => ['VND-DEMO-AGT', 'Demo Global Agent', 'international_agent', 'Malaysia', 'Maybank', 'Demo Global Agent'],
        ];

        $result = [];
        foreach ($rows as $key => [$code, $name, $type, $country, $bank, $accountName]) {
            $vendor = Vendor::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'email' => strtolower(str_replace(' ', '.', $name)).'@demo.test',
                    'phone' => '021-777-'.str_pad((string) random_int(10, 99), 4, '0', STR_PAD_LEFT),
                    'address' => 'Demo Vendor Office, '.$country,
                    'country' => $country,
                    'bank_name' => $bank,
                    'bank_account_number' => '777000'.random_int(100, 999),
                    'bank_account_name' => $accountName,
                    'pic' => 'PIC '.$accountName,
                    'is_active' => true,
                ]
            );
            VendorCategory::firstOrCreate(['vendor_id' => $vendor->id, 'category' => $type]);
            $result[$key] = $vendor;
        }

        return $result;
    }

    private function pricing(Vendor $truckingVendor): void
    {
        foreach ([
            ['WEEK-DEMO-IDR', today()->startOfWeek(), 'IDR', '1.00', null],
            ['WEEK-DEMO-USD', today()->startOfWeek(), 'USD', '16500.00', 'exp_sea'],
            ['WEEK-DEMO-SGD', today()->startOfWeek(), 'SGD', '12200.00', 'imp_sea'],
        ] as [$week, $date, $currency, $rate, $service]) {
            WeeklyPricing::updateOrCreate(
                ['week' => $week, 'currency' => $currency, 'service' => $service],
                ['effective_date' => $date, 'effective_until' => null, 'exchange_rate' => $rate, 'notes' => 'Demo rate', 'is_active' => true]
            );
        }

        foreach ([
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', 'cdd', false, '1850000', '2400000'],
            ['TANJUNG PRIOK, INDONESIA', 'BEKASI, INDONESIA', '20ft', false, '2800000', '3600000'],
            ['SOEKARNO HATTA, INDONESIA', 'TANGERANG, INDONESIA', 'blindvan', false, '950000', '1350000'],
        ] as [$origin, $destination, $container, $overweight, $cost, $sell]) {
            TruckingPrice::updateOrCreate(
                ['port_origin' => $origin, 'destination' => $destination, 'container_type' => $container, 'overweight' => $overweight, 'vendor_id' => $truckingVendor->id],
                ['price' => $cost, 'selling_price' => $sell, 'currency' => 'IDR', 'effective_date' => today()->subDays(10), 'effective_until' => null, 'is_active' => true]
            );
        }
    }

    private function tps(): void
    {
        foreach ([
            ['Jakarta', 'TPS Demo Tanjung Priok', 'TPS-SEA-DEMO', 'sea'],
            ['Tangerang', 'TPS Demo Bandara Soekarno Hatta', 'TPS-AIR-DEMO', 'air'],
        ] as [$city, $name, $code, $mode]) {
            Tps::updateOrCreate(['code' => $code], ['city' => $city, 'name' => $name, 'mode' => $mode, 'is_active' => true]);
        }
    }

    private function jobs(array $customers, array $users): array
    {
        $quotationService = app(QuotationService::class);
        $jobService = app(JobService::class);
        $costService = app(JobCostService::class);
        $closingService = app(JobClosingService::class);
        $paymentService = app(PaymentService::class);

        $cases = [
            'exp_sea' => [$customers['sea'], 'Demo Export Sea Jakarta ke Port Klang', 'exp_sea', 'JAKARTA, INDONESIA', 'PORT KLANG NORTH, MALAYSIA', 'HOUSE BL DEMO / NPE DEMO', 'npe'],
            'exp_air' => [$customers['air'], 'Demo Export Air Jakarta ke Singapore', 'exp_air', 'SOEKARNO HATTA, INDONESIA', 'CHANGI, SINGAPORE', 'HOUSE AWB DEMO / NPE DEMO', 'npe'],
            'imp_sea' => [$customers['import'], 'Demo Import Sea Singapore ke Jakarta', 'imp_sea', 'SINGAPORE, SINGAPORE', 'TANJUNG PRIOK, INDONESIA', 'SPJM dan SPPB demo', 'sppb'],
            'imp_air' => [$customers['domestic'], 'Demo Import Air Kuala Lumpur ke Jakarta', 'imp_air', 'KUALA LUMPUR, MALAYSIA', 'SOEKARNO HATTA, INDONESIA', 'SPJM demo inspection', 'spjm'],
        ];

        $jobs = [];
        foreach ($cases as $key => [$customer, $subject, $service, $origin, $destination, $note, $shipmentStatus]) {
            $existing = Job::where('subject', $subject)->first();
            if ($existing) {
                $jobs[$key] = $existing;
                continue;
            }

            $quotation = $quotationService->save(null, [
                'customer_id' => $customer->id,
                'sales_id' => $users['sales']->id,
                'subject' => $subject,
                'quotation_date' => today()->subDays(7)->toDateString(),
                'valid_until' => today()->addDays(21)->toDateString(),
                'notes' => $note,
                'service_type' => $service,
                'origin' => $origin,
                'destination' => $destination,
                'shipper_name' => $customer->name,
                'shipper_address' => $customer->address,
                'consignee_name' => $customer->name.' Consignee',
                'consignee_address' => 'Demo destination address',
                'terms_of_delivery' => str_starts_with($service, 'imp') ? 'CIF' : 'FOB',
                'cargo_qty' => '12',
                'weight_meas' => '15000',
                'commodity' => str_contains($subject, 'Air') ? 'Electronic Parts' : 'General Cargo',
                'currency' => 'IDR',
                'exchange_rate' => '1.00',
                'payment_terms' => $customer->default_payment_terms,
                'tax_rate' => '0',
                'discount' => '0',
                'items' => [
                    ['description' => 'Temporary dokumen', 'type' => 'temporary', 'unit' => 'Paket', 'quantity' => '1', 'unit_cost' => '1500000', 'unit_price' => '1500000'],
                    ['description' => 'Jasa handling '.$service, 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3500000', 'unit_price' => '5000000'],
                ],
            ], $users['sales']);

            $quotationService->transition($quotation, 'submit', ['lock_version' => $quotation->fresh()->lock_version], $users['sales']);
            $quotationService->transition($quotation, 'approve', ['lock_version' => $quotation->fresh()->lock_version], $users['sales_manager']);
            $job = $quotationService->convert($quotation, ['lock_version' => $quotation->fresh()->lock_version], $users['sales_manager']);
            $jobService->transition($job, 'open', ['lock_version' => $job->fresh()->lock_version], $users['operation']);
            $job = $job->fresh();
            $job->update([
                'cs_id' => $users['operation']->id,
                'etd' => today()->addDays(5),
                'eta' => today()->addDays(12),
                'vessel_voyage' => str_contains($service, 'air') ? null : 'WAN HAI DEMO / W046',
                'flight_number' => str_contains($service, 'air') ? 'GA-DEMO-088' : null,
                'bl_number' => str_contains($service, 'sea') ? 'MBL-DEMO-'.strtoupper($key) : null,
                'hbl_number' => str_contains($service, 'sea') ? 'HBL-DEMO-'.strtoupper($key) : null,
                'awb_number' => str_contains($service, 'air') ? 'MAWB-DEMO-'.strtoupper($key) : null,
                'hawb_number' => str_contains($service, 'air') ? 'HAWB-DEMO-'.strtoupper($key) : null,
                'booking_reference' => match ($key) { 'exp_sea' => '260200', 'exp_air' => '260201', 'imp_sea' => '260904', default => '260905' },
                'nopen' => str_starts_with($service, 'imp') ? 'NOPEN-'.strtoupper($key) : null,
                'peb_number' => str_starts_with($service, 'exp') ? '415575' : null,
                'peb_date' => str_starts_with($service, 'exp') ? today()->subDays(2) : null,
                'npe_number' => str_starts_with($service, 'exp') ? '415522/PM/KPU.1/2026' : null,
                'shipment_status' => $shipmentStatus,
                'shipment_status_by' => $users['operation']->id,
                'shipment_status_at' => now(),
                'updated_by' => $users['operation']->id,
            ]);

            foreach ($job->fresh()->costs()->where('status', '!=', 'final')->get() as $cost) {
                $costService->finalize($job->fresh(), $cost, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $cost->lock_version], $users['finance']);
            }

            if ($key === 'exp_sea') {
                $invoice = $closingService->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '0'], $users['finance']);
                $paymentService->create($invoice, ['lock_version' => $invoice->fresh()->lock_version, 'payment_date' => today()->toDateString(), 'amount' => '3000000', 'deposit_account' => 'bank', 'method' => 'transfer', 'reference' => 'DEMO-PAY-001'], $users['finance']);
            }

            $jobs[$key] = $job->fresh();
        }

        return $jobs;
    }

    private function operationalDocuments(array $jobs, array $customers, User $operation): void
    {
        foreach (['exp_sea', 'exp_air'] as $key) {
            if (empty($jobs[$key])) {
                continue;
            }
            $job = $jobs[$key]->fresh();
            BookingConfirmation::firstOrCreate(
                ['number' => 'BC-DEMO-'.strtoupper($key)],
                ['booking_date' => today(), 'job_id' => $job->id, 'customer_id' => $job->customer_id, 'contact_person' => $job->customer?->contact_name, 'shipper_name' => $job->shipper_name, 'carrier_name' => $key === 'exp_sea' ? 'Demo Ocean Line' : 'Demo Air Cargo', 'carrier_booking_no' => 'BOOK-DEMO-'.strtoupper($key), 'vessel_voyage' => $job->vessel_voyage ?? $job->flight_number, 'pol' => $job->pol, 'pod' => $job->pod, 'etd' => $job->etd, 'eta' => $job->eta, 'quantity' => '12 PKG', 'cargo_description' => $job->cargo_description, 'gross_weight' => $job->gross_weight, 'volume' => $job->volume, 'status' => 'confirmed', 'created_by' => $operation->id]
            );
            ShippingInstruction::firstOrCreate(
                ['number' => 'SI-DEMO-'.strtoupper($key)],
                ['si_date' => today(), 'job_id' => $job->id, 'customer_id' => $job->customer_id, 'to_carrier' => $key === 'exp_sea' ? 'Demo Ocean Line' : 'Demo Air Cargo', 'shipper_name' => $job->shipper_name, 'shipper_address' => $job->shipper_address, 'consignee_name' => $job->consignee_name, 'consignee_address' => $job->consignee_address, 'vessel_voyage' => $job->vessel_voyage ?? $job->flight_number, 'etd' => $job->etd, 'eta' => $job->eta, 'pol' => $job->pol, 'pod' => $job->pod, 'cargo_description' => $job->cargo_description, 'gross_weight' => $job->gross_weight, 'measurement' => $job->volume, 'status' => 'submitted', 'created_by' => $operation->id]
            );
        }
    }

    private function reimbursements(array $jobs, array $vendors, array $users): void
    {
        $job = $jobs['imp_sea'] ?? null;
        Reimbursement::firstOrCreate(
            ['number' => 'RMB-DEMO-0001'],
            ['employee_id' => $users['operation']->id, 'job_id' => $job?->id, 'vendor_id' => $vendors['trucking']->id, 'category' => 'transport', 'reimbursement_date' => today()->subDays(1), 'description' => 'Demo transport dokumen ke pelabuhan', 'amount' => '350000', 'currency' => 'IDR', 'exchange_rate' => '1.00', 'status' => 'pending', 'created_by' => $users['operation']->id, 'lock_version' => 0]
        );
    }
}
