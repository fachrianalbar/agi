<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['name' => 'Dashboard', 'section' => 'Main Menu', 'icon' => 'dashboard', 'route_name' => 'dashboard', 'active_pattern' => 'dashboard', 'sort_order' => 10],
            ['name' => 'AI Agents', 'section' => 'Main Menu', 'icon' => 'agents', 'route_name' => 'agents.index', 'active_pattern' => 'agents.*', 'sort_order' => 20],
            ['name' => 'Analytics', 'section' => 'Main Menu', 'icon' => 'analytics', 'route_name' => 'analytics', 'active_pattern' => 'analytics', 'sort_order' => 30],
            ['name' => 'Activity', 'section' => 'Main Menu', 'icon' => 'activity', 'route_name' => 'activity', 'active_pattern' => 'activity', 'sort_order' => 40],
            ['name' => 'Settings', 'section' => 'Administrator', 'icon' => 'settings', 'route_name' => 'settings', 'active_pattern' => 'settings', 'sort_order' => 10],
            ['name' => 'Menu Management', 'section' => 'Administrator', 'icon' => 'menu', 'route_name' => 'menus.index', 'active_pattern' => 'menus.*', 'sort_order' => 20],
        ];

        foreach ($menus as $menu) {
            Menu::query()->updateOrCreate(
                ['route_name' => $menu['route_name']],
                [...$menu, 'target' => '_self', 'is_active' => true],
            );
        }
    }
}
