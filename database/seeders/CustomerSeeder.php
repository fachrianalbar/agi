<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name'     => 'Alex Kim',
                'username' => 'alexkim',
                'email'    => 'alex@agentix.ai',
                'password' => 'password123',
                'phone'    => '+1-555-0101',
                'city'     => 'San Francisco',
                'country'  => 'United States',
            ],
            [
                'name'     => 'Sarah Chen',
                'username' => 'sarahchen',
                'email'    => 'sarah@acmecorp.com',
                'password' => 'password123',
                'phone'    => '+1-555-0102',
                'city'     => 'New York',
                'country'  => 'United States',
            ],
            [
                'name'     => 'Marco Rossi',
                'username' => 'marcorossi',
                'email'    => 'marco@example.it',
                'password' => 'password123',
                'phone'    => '+39-010-5555',
                'city'     => 'Milan',
                'country'  => 'Italy',
            ],
        ];

        foreach ($customers as $data) {
            Customer::query()->updateOrCreate(
                ['email' => $data['email']],
                $data,
            );
        }
    }
}
