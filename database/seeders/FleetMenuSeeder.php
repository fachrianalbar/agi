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

            Menu::query()
                ->where('name', 'Non Active Fleet')
                ->where('section', 'Fleet')
                ->delete();

            Menu::query()->updateOrCreate(
                [
                    'route_name' => 'summary-reports.index',
                ],
                [
                    'parent_id' => null,
                    'name' => 'Summary Report',
                    'section' => 'Fleet',
                    'icon' => 'analytics',
                    'url' => null,
                    'active_pattern' => 'summary-reports.*',
                    'target' => '_self',
                    'sort_order' => 20,
                    'is_active' => true,
                ],
            );

            Menu::query()->updateOrCreate(
                [
                    'route_name' => 'fleet-histories.index',
                ],
                [
                    'parent_id' => null,
                    'name' => 'Fleet History',
                    'section' => 'Fleet',
                    'icon' => 'activity',
                    'url' => null,
                    'active_pattern' => 'fleet-histories.*',
                    'target' => '_self',
                    'sort_order' => 30,
                    'is_active' => true,
                ],
            );
            $legacyParent?->delete();
        });
    }
}
