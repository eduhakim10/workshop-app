<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerPortalDemoSeeder extends Seeder
{
    /**
     * Creates a demo customer portal login. Run:
     *   php artisan db:seed --class=CustomerPortalDemoSeeder
     */
    public function run(): void
    {
        $customer = Customer::query()->first()
            ?? Customer::create([
                'name' => 'PT Demo Customer',
                'email' => 'contact@democustomer.co.id',
                'phone' => '021-0000000',
                'address' => 'Jakarta',
            ]);

        CustomerUser::updateOrCreate(
            ['email' => 'admin@customer.mti.co.id'],
            [
                'customer_id' => $customer->id,
                'name' => 'Admin Customer',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'is_active' => true,
            ]
        );

        $this->command?->info('Demo customer login: admin@customer.mti.co.id / password (customer: ' . $customer->name . ')');
    }
}
