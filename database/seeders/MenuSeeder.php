<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['name' => 'Users', 'section' => 'Administrator', 'icon' => 'agents', 'route_name' => 'users.index', 'active_pattern' => 'users.*', 'sort_order' => 5],
            ['name' => 'Customers', 'section' => 'Administrator', 'icon' => 'agents', 'route_name' => 'customers.index', 'active_pattern' => 'customers.*', 'sort_order' => 10],
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
