<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->text('latest_address')->nullable();
            $table->string('latest_mileage', 50)->nullable();
            $table->string('latest_vehicle_status', 30)->nullable();
            $table->string('latest_engine', 20)->nullable();
            $table->string('latest_update', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn([
                'latest_address',
                'latest_mileage',
                'latest_vehicle_status',
                'latest_engine',
                'latest_update',
            ]);
        });
    }
};
