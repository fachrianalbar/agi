<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropUnique('fleets_vehicle_device_unique');
            $table->unique(['customer_id', 'vehicle_name', 'device_name'], 'fleets_customer_vehicle_device_unique');
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropUnique('fleets_customer_vehicle_device_unique');
            $table->unique(['vehicle_name', 'device_name'], 'fleets_vehicle_device_unique');
        });
    }
};
