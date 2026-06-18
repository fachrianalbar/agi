<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fleet extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'vehicle_name',
        'device_name',
        'has_fuel_sensor',
        'fuel_sensor_installed_at',
        'fuel_sensor_status',
        'latest_address',
        'latest_mileage',
        'latest_vehicle_status',
        'latest_engine',
        'latest_update',
        'is_active',
    ];

    protected $casts = [
        'has_fuel_sensor' => 'boolean',
        'fuel_sensor_installed_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FleetTransaction::class);
    }
}
