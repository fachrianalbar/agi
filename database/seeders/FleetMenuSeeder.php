<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FleetMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $legacyParent = Menu::query()
                ->whereNull('parent_id')
                ->where('name', 'Fleet')
                ->first();

            $allFleetMenu = Menu::query()
                ->where('route_name', 'fleets.index')
                ->first();

            if ($allFleetMenu) {
                $allFleetMenu->update([
                    'parent_id' => null,
                    'name' => 'All Fleet',
                    'section' => 'Fleet',
                    'icon' => 'truck',
                    'url' => null,
                    'active_pattern' => 'fleets.*',
                    'target' => '_self',
                    'sort_order' => 10,
                    'is_active' => true,
                ]);
            } else {
                Menu::query()->create([
                    'parent_id' => null,
                    'name' => 'All Fleet',
                    'section' => 'Fleet',
                    'icon' => 'truck',
                    'route_name' => 'fleets.index',
                    'url' => null,
                    'active_pattern' => 'fleets.*',
                    'target' => '_self',
                    'sort_order' => 10,
                    'is_active' => true,
                ]);
            }

            Menu::query()->updateOrCreate(
                [
                    'name' => 'Non Active Fleet',
                ],
                [
                    'parent_id' => null,
                    'section' => 'Fleet',
                    'icon' => 'inactive',
                    'route_name' => null,
                    'url' => null,
                    'active_pattern' => null,
                    'target' => '_self',
                    'sort_order' => 20,
                    'is_active' => true,
                ],
            );

            $legacyParent?->delete();
        });
    }
}
