<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->string('fuel_sensor_status', 20)
                ->default('inactive')
                ->after('fuel_sensor_installed_at');
        });

        DB::table('fleets')
            ->where('has_fuel_sensor', true)
            ->update(['fuel_sensor_status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn('fuel_sensor_status');
        });
    }
};
