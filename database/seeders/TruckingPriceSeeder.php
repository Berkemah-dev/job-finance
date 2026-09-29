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
        $superAdminRole = \App\Models\Role::where('name', 'super-admin')->first();
        $admin = User::first() ?? User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@jobfinance.test',
            'password' => bcrypt('JobFinance!2026'),
            'role_id' => $superAdminRole?->id,
            'is_active' => true,
        ]);
        if (! $admin->role_id && $superAdminRole) {
            $admin->update(['role_id' => $superAdminRole->id, 'is_active' => true]);
        }

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

        // Armada & Supir Vendor 1 (PT ARMADA TRUCKING NUSANTARA)
        \App\Models\VendorTruck::updateOrCreate(
            ['vendor_id' => $vendor->id, 'plate_number' => 'B 9210 UE'],
            ['driver_name' => 'Bambang Supriyadi', 'driver_phone' => '0812-8877-6655', 'vehicle_type' => 'Trailer 40ft', 'notes' => 'Armada Utama', 'is_active' => true]
        );
        \App\Models\VendorTruck::updateOrCreate(
            ['vendor_id' => $vendor->id, 'plate_number' => 'B 9554 TX'],
            ['driver_name' => 'Joko Purwanto', 'driver_phone' => '0813-1122-3344', 'vehicle_type' => 'Trailer 20ft', 'notes' => 'Armada Cadangan', 'is_active' => true]
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

        // Armada & Supir Vendor 2 (PT LOGISTIK LINTAS PULAU)
        \App\Models\VendorTruck::updateOrCreate(
            ['vendor_id' => $vendor2->id, 'plate_number' => 'D 8841 AB'],
            ['driver_name' => 'Dedi Mulyadi', 'driver_phone' => '0821-4455-6677', 'vehicle_type' => 'Tronton Wingbox', 'notes' => 'Armada Wingbox', 'is_active' => true]
        );
        \App\Models\VendorTruck::updateOrCreate(
            ['vendor_id' => $vendor2->id, 'plate_number' => 'B 9912 KAA'],
            ['driver_name' => 'Asep Hidayat', 'driver_phone' => '0857-9988-1122', 'vehicle_type' => 'Trailer 40ft', 'notes' => 'Armada Trailer', 'is_active' => true]
        );

        // RUTE TANJUNG PRIOK -> SURABAYA:
        // Harga Jual CUMAN 1 untuk semua type contoh 20GP/40FT/40HQ (Rp 7.500.000)
        // Di dalamnya ada list modal beberapa vendor (PT ARMADA & PT LOGISTIK LINTAS PULAU)
        $rates = [
            // Rute 1 - Vendor 1: PT Armada Trucking Nusantara
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '20gp', false, '3500000.00', '7500000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '20gp', true, '4200000.00', '7500000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40ft', false, '6000000.00', '7500000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40ft', true, '7100000.00', '7500000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40hq', false, '6500000.00', '7500000.00', $vendor->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40hq', true, '7800000.00', '7500000.00', $vendor->id],

            // Rute 1 - Vendor 2: PT Logistik Lintas Pulau (Modal alternatif di rute yang sama)
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '20gp', false, '3300000.00', '7500000.00', $vendor2->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '20gp', true, '3900000.00', '7500000.00', $vendor2->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40ft', false, '5800000.00', '7500000.00', $vendor2->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40ft', true, '6800000.00', '7500000.00', $vendor2->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40hq', false, '6200000.00', '7500000.00', $vendor2->id],
            ['TANJUNG PRIOK, INDONESIA', 'SURABAYA, INDONESIA', '40hq', true, '7400000.00', '7500000.00', $vendor2->id],

            // Rute 2: Jakarta -> Bandung (PT Logistik Lintas Pulau) - 1 Harga Jual Rp 4.500.000
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '20gp', false, '2200000.00', '4500000.00', $vendor2->id],
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '20gp', true, '2700000.00', '4500000.00', $vendor2->id],
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '40ft', false, '3800000.00', '4500000.00', $vendor2->id],
            ['JAKARTA, INDONESIA', 'BANDUNG, INDONESIA', '40hq', false, '4100000.00', '4500000.00', $vendor2->id],

            // Rute 3: Soekarno Hatta -> Tangerang (Tarif Umum)
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
