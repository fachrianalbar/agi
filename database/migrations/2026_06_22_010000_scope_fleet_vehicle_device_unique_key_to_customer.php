<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('fleets'))->keyBy('name');

        Schema::table('fleets', function (Blueprint $table) use ($indexes) {
            if ($indexes->has('fleets_vehicle_device_unique')) {
                $table->dropUnique('fleets_vehicle_device_unique');
            }

            if (! $indexes->has('fleets_customer_vehicle_device_unique')) {
                $table->unique(['customer_id', 'vehicle_name', 'device_name'], 'fleets_customer_vehicle_device_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('fleets'))->keyBy('name');

        Schema::table('fleets', function (Blueprint $table) use ($indexes) {
            if ($indexes->has('fleets_customer_vehicle_device_unique')) {
                $table->dropUnique('fleets_customer_vehicle_device_unique');
            }

            if (! $indexes->has('fleets_vehicle_device_unique')) {
                $table->unique(['vehicle_name', 'device_name'], 'fleets_vehicle_device_unique');
            }
        });
    }
};
