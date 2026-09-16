<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields needed to print a proper GST Road/Delivery Challan alongside the
     * existing Dispatch Sheet PDF: who the goods are billed to (phone, GSTIN,
     * place of supply) and how they're travelling (L.R. number, E-Way bill
     * number, transporter name/ID). Vehicle number, driver name and driver
     * phone already existed and are reused as-is.
     */
    public function up(): void
    {
        Schema::table('dispatch_sheets', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
            $table->string('customer_gstin', 20)->nullable()->after('delivery_address');
            $table->string('place_of_supply', 100)->nullable()->after('customer_gstin');
            $table->string('lr_no', 100)->nullable()->after('vehicle_no');
            $table->string('eway_no', 50)->nullable()->after('lr_no');
            $table->string('transport_name', 255)->nullable()->after('eway_no');
            $table->string('transport_id', 100)->nullable()->after('transport_name');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_sheets', function (Blueprint $table) {
            $table->dropColumn([
                'customer_phone', 'customer_gstin', 'place_of_supply',
                'lr_no', 'eway_no', 'transport_name', 'transport_id',
            ]);
        });
    }
};
