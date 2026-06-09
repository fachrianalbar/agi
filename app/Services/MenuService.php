<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MenuService
{
    public function getDataTableQuery(): Builder
    {
        return Menu::query()
            ->select('menus.*');
    }

    public function getSidebarMenus(): Collection
    {
        if (! Schema::hasTable('menus')) {
            return collect();
        }

        return Menu::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('section')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('section');
    }

    public function create(array $data): Menu
    {
        return DB::transaction(fn () => Menu::query()->create([
            ...$data,
            'parent_id' => null,
        ]));
    }

    public function update(Menu $menu, array $data): Menu
    {
        return DB::transaction(function () use ($menu, $data) {
            $menu->update([
                ...$data,
                'parent_id' => null,
            ]);

            return $menu->refresh();
        });
    }

    public function delete(Menu $menu): void
    {
        DB::transaction(fn () => $menu->delete());
    }
}
