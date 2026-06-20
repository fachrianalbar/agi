<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_username_allows_spaces_capitalization_and_special_characters(): void
    {
        $username = 'PT. Maju Jaya / Admin #1';

        $this->post(route('customers.store'), $this->customerData([
            'username' => $username,
        ]))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'username' => $username,
        ]);
    }

    public function test_customer_username_must_remain_unique(): void
    {
        Customer::query()->create($this->customerData());

        $this->from(route('customers.create'))
            ->post(route('customers.store'), $this->customerData([
                'email' => 'other@example.com',
            ]))
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('username');
    }

    private function customerData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Customer Test',
            'username' => 'PT. Maju Jaya / Admin #1',
            'email' => 'customer@example.com',
            'password' => 'secret',
            'is_active' => true,
        ], $overrides);
    }
}
