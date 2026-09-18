<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'container_number')) {
                $table->string('container_number', 120)->nullable()->after('container_type');
            }
            if (!Schema::hasColumn('jobs', 'vendor_trucking_id')) {
                $table->foreignId('vendor_trucking_id')->nullable()->after('container_number')->constrained('vendors')->nullOnDelete();
            }
            if (!Schema::hasColumn('jobs', 'vendor_truck_id')) {
                $table->foreignId('vendor_truck_id')->nullable()->after('vendor_trucking_id')->constrained('vendor_trucks')->nullOnDelete();
            }
            if (!Schema::hasColumn('jobs', 'customer_address_id')) {
                $table->foreignId('customer_address_id')->nullable()->after('vendor_truck_id')->constrained('customer_addresses')->nullOnDelete();
            }
            if (!Schema::hasColumn('jobs', 'truck_plate_number')) {
                $table->string('truck_plate_number', 30)->nullable()->after('customer_address_id');
            }
            if (!Schema::hasColumn('jobs', 'driver_name')) {
                $table->string('driver_name', 160)->nullable()->after('truck_plate_number');
            }
            if (!Schema::hasColumn('jobs', 'driver_phone')) {
                $table->string('driver_phone', 50)->nullable()->after('driver_name');
            }
            if (!Schema::hasColumn('jobs', 'vehicle_type')) {
                $table->string('vehicle_type', 60)->nullable()->after('driver_phone');
            }
            if (!Schema::hasColumn('jobs', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('vehicle_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $foreignKeys = ['vendor_trucking_id', 'vendor_truck_id', 'customer_address_id'];
            foreach ($foreignKeys as $fk) {
                if (Schema::hasColumn('jobs', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }

            $columns = [
                'container_number',
                'truck_plate_number',
                'driver_name',
                'driver_phone',
                'vehicle_type',
                'delivery_address',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('jobs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
