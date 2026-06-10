<?php

namespace Tests\Feature;

use App\Models\Menu;
use Database\Seeders\FleetMenuSeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_menus_are_rendered_from_the_database(): void
    {
        $this->seed(MenuSeeder::class);
        $menu = Menu::query()->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Menu Management')
            ->assertSee(route('menus.index'));

        $this->get(route('menus.index'))
            ->assertOk()
            ->assertDontSee($menu->id);
    }

    public function test_menu_datatable_returns_server_side_json(): void
    {
        $this->seed(MenuSeeder::class);

        $response = $this->getJson(route('menus.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Customers', 'regex' => 'false'],
            'columns' => [
                [
                    'data' => 'menu',
                    'name' => 'name',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
            ],
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.section', 'Administrator')
            ->assertJsonMissingPath('data.0.id');
    }

    public function test_fleet_menu_seeder_creates_flat_section_menus_idempotently(): void
    {
        Menu::query()->create([
            'name' => 'Fleets',
            'section' => 'Administrator',
            'icon' => 'activity',
            'route_name' => 'fleets.index',
            'active_pattern' => 'fleets.*',
            'target' => '_self',
            'sort_order' => 15,
            'is_active' => true,
        ]);

        $this->seed(FleetMenuSeeder::class);
        $this->seed(FleetMenuSeeder::class);

        Menu::query()->create([
            'name' => 'Non Active Fleet',
            'section' => 'Fleet',
            'icon' => 'inactive',
            'target' => '_self',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->seed(FleetMenuSeeder::class);

        $this->assertDatabaseHas('menus', [
            'parent_id' => null,
            'name' => 'All Fleet',
            'section' => 'Fleet',
            'icon' => 'truck',
            'route_name' => 'fleets.index',
        ]);
        $this->assertDatabaseHas('menus', [
            'parent_id' => null,
            'name' => 'Summary Report',
            'section' => 'Fleet',
            'icon' => 'analytics',
            'route_name' => 'summary-reports.index',
            'active_pattern' => 'summary-reports.*',
        ]);
        $this->assertDatabaseHas('menus', [
            'parent_id' => null,
            'name' => 'Fleet History',
            'section' => 'Fleet',
            'icon' => 'activity',
            'route_name' => 'fleet-histories.index',
            'active_pattern' => 'fleet-histories.*',
        ]);
        $this->assertDatabaseMissing('menus', [
            'parent_id' => null,
            'name' => 'Fleet',
        ]);
        $this->assertDatabaseMissing('menus', [
            'parent_id' => null,
            'name' => 'Non Active Fleet',
            'section' => 'Fleet',
        ]);
        $this->assertDatabaseCount('menus', 3);
    }

    public function test_menu_can_be_created_with_a_ulid(): void
    {
        $this->get(route('menus.create'))
            ->assertOk()
            ->assertSee('Displayed uppercase as a sidebar separator')
            ->assertDontSee('Parent Menu');

        $response = $this->post(route('menus.store'), [
            'name' => 'Documentation',
            'section' => 'Workspace',
            'icon' => 'link',
            'url' => '/documentation',
            'active_pattern' => null,
            'target' => '_self',
            'sort_order' => 30,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('menus.index'));

        $menu = Menu::query()->where('name', 'Documentation')->firstOrFail();

        $this->assertTrue(Str::isUlid($menu->id));
        $this->assertTrue($menu->is_active);
    }

    public function test_menu_can_be_updated_and_deleted(): void
    {
        $menu = Menu::query()->create([
            'name' => 'Old Name',
            'section' => 'Main Menu',
            'icon' => 'circle',
            'route_name' => 'dashboard',
            'target' => '_self',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->put(route('menus.update', $menu), [
            'name' => 'New Name',
            'section' => 'Workspace',
            'icon' => 'menu',
            'route_name' => 'menus.index',
            'active_pattern' => 'menus.*',
            'target' => '_self',
            'sort_order' => 20,
        ])->assertRedirect(route('menus.index'));

        $this->assertDatabaseHas('menus', [
            'id' => $menu->id,
            'name' => 'New Name',
            'is_active' => false,
        ]);

        $this->delete(route('menus.destroy', $menu))
            ->assertRedirect(route('menus.index'));

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_menu_can_be_created_as_a_flat_placeholder(): void
    {
        $parent = Menu::query()->create([
            'name' => 'Parent',
            'section' => 'Main Menu',
            'icon' => 'menu',
            'url' => '/parent',
            'target' => '_self',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->post(route('menus.store'), [
            'parent_id' => $parent->id,
            'name' => 'Coming Soon',
            'section' => 'Fleet',
            'icon' => 'inactive',
            'target' => '_self',
            'sort_order' => 20,
            'is_active' => '1',
        ])->assertRedirect(route('menus.index'));

        $this->assertDatabaseHas('menus', [
            'parent_id' => null,
            'name' => 'Coming Soon',
            'section' => 'Fleet',
            'route_name' => null,
            'url' => null,
        ]);
    }

    public function test_route_name_must_be_registered(): void
    {
        $this->from(route('menus.create'))
            ->post(route('menus.store'), [
                'name' => 'Broken Link',
                'section' => 'Main Menu',
                'icon' => 'link',
                'route_name' => 'route.does.not.exist',
                'target' => '_self',
                'sort_order' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('menus.create'))
            ->assertSessionHasErrors('route_name');

        $this->assertDatabaseMissing('menus', ['name' => 'Broken Link']);
    }

    public function test_menu_can_be_deleted_with_an_ajax_request(): void
    {
        $menu = Menu::query()->create([
            'name' => 'Delete Me',
            'section' => 'Main Menu',
            'icon' => 'circle',
            'url' => '/delete-me',
            'target' => '_self',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->deleteJson(route('menus.destroy', $menu))
            ->assertOk()
            ->assertJsonPath('message', 'Menu "Delete Me" deleted successfully.');

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }
}
