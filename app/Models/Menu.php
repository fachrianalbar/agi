<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

class Menu extends Model
{
    use HasUlids;

    public const ICONS = [
        'activity',
        'agents',
        'analytics',
        'check',
        'circle',
        'dashboard',
        'inactive',
        'link',
        'menu',
        'settings',
        'truck',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'section',
        'icon',
        'route_name',
        'url',
        'active_pattern',
        'target',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function destinationUrl(): string
    {
        if ($this->route_name && Route::has($this->route_name)) {
            return route($this->route_name);
        }

        return $this->url ?: '#';
    }

    public function isCurrent(): bool
    {
        if ($this->active_pattern) {
            return request()->routeIs($this->active_pattern);
        }

        if ($this->route_name && Route::has($this->route_name)) {
            return request()->routeIs($this->route_name);
        }

        if ($this->url && str_starts_with($this->url, '/')) {
            return request()->is(ltrim($this->url, '/'));
        }

        return false;
    }
}
