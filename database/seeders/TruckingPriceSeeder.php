<?php

namespace Database\Seeders;

use App\Models\TruckingPrice;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class TruckingPriceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first() ?? User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@jobfinance.test',
            'password' => bcrypt('JobFinance!2026'),
        ]);

        $vendor = Vendor::firstOrCreate(
            ['code' => 'VND-TRK-01'],
            [
                'name' => 'PT ARMADA TRUCKING NUSANTARA',
                'type' => 'trucking',
                'phone' => '021-888999',
                'email' => 'vendor.trucking@example.com',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $vendor2 = Vendor::firstOrCreate(
            ['code' => 'VND-TRK-02'],
            [
                'name' => 'PT LOGISTIK LINTAS PULAU',
                'type' => 'trucking',
                'phone' => '021-777666',
                'email' => 'lintas.pulau@example.com',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $rates = [
            // Rute 1: Tanjung Priok -> Surabaya (PT Armada Trucking Nusantara)
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '20gp', false, '3500000.00', '4800000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '20gp', true, '4200000.00', '5600000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40ft', false, '6000000.00', '7800000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40ft', true, '7100000.00', '9000000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40hq', false, '6500000.00', '8200000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40hq', true, '7800000.00', '9800000.00', $vendor->id],

            // Rute 2: Jakarta -> Bandung (PT Logistik Lintas Pulau)
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '20gp', false, '2200000.00', '3100000.00', $vendor2->id],
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '20gp', true, '2700000.00', '3600000.00', $vendor2->id],
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '40ft', false, '3800000.00', '5000000.00', $vendor2->id],
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '40hq', false, '4100000.00', '5400000.00', $vendor2->id],

            // Rute 3: Soekarno Hatta -> Tangerang (Tarif Umum / tanpa vendor)
            ['SOEKARNO HATTA, INDONESIA', 'TANGERANG, INDONESIA', 'cdd', false, '1200000.00', '1650000.00', null],
            ['SOEKARNO HATTA, INDONESIA', 'TANGERANG, INDONESIA', 'blindvan', false, '850000.00', '1200000.00', null],
        ];

        foreach ($rates as [$origin, $destination, $container, $overweight, $cost, $sell, $vendorId]) {
            TruckingPrice::updateOrCreate(
                [
                    'port_origin' => $origin,
                    'destination' => $destination,
                    'container_type' => $container,
                    'overweight' => $overweight,
                    'vendor_id' => $vendorId,
                ],
                [
                    'price' => $cost,
                    'selling_price' => $sell,
                    'currency' => 'IDR',
                    'effective_date' => today()->subMonth(),
                    'effective_until' => null,
                    'is_active' => true,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }
    }
}
