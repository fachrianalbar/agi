<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_access_can_be_cleared_to_allow_all_customers(): void
    {
        $customer = Customer::query()->create([
            'name' => 'AGI Customer',
            'username' => 'agi',
            'email' => 'agi@example.com',
            'password' => 'plain-api-password',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['customer_id' => $customer->id]);

        $this->put(route('users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => '',
            'customer_id' => '',
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'customer_id' => null,
        ]);
    }
}
