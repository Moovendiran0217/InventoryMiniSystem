<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Avery Johnson', 'email' => 'avery@example.com'],
            ['name' => 'Jordan Lee', 'email' => 'jordan@example.com'],
            ['name' => 'Morgan Patel', 'email' => 'morgan@example.com'],
            ['name' => 'Taylor Rivera', 'email' => 'taylor@example.com'],
            ['name' => 'Casey Kim', 'email' => 'casey@example.com'],
        ];

        foreach ($customers as $customer) {
            Customer::query()->firstOrCreate(
                ['email' => $customer['email']],
                ['name' => $customer['name']],
            );
        }
    }
}
