<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same transport/logistics fields as dispatch_sheets, so a Stock
     * Transfer's Road Challan can carry the same information as the sales
     * Delivery Challan (vehicle, driver, L.R./E-Way numbers, transporter).
     */
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('vehicle_no', 50)->nullable()->after('notes');
            $table->string('driver_name', 255)->nullable()->after('vehicle_no');
            $table->string('driver_phone', 20)->nullable()->after('driver_name');
            $table->string('lr_no', 100)->nullable()->after('driver_phone');
            $table->string('eway_no', 50)->nullable()->after('lr_no');
            $table->string('transport_name', 255)->nullable()->after('eway_no');
            $table->string('transport_id', 100)->nullable()->after('transport_name');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_no', 'driver_name', 'driver_phone',
                'lr_no', 'eway_no', 'transport_name', 'transport_id',
            ]);
        });
    }
};
