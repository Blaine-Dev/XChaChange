<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrenciesSeeder extends Seeder
{
    /**
     * Seed the currencies table.
     */
    public function run(): void
    {
        $currencies = [
            [
                'currency' => 'USD',
                'exchange_rate' => 0.0808279,
                'surcharge_percentage' => 7.5,
                'special_discount_percentage' => 0.0,
                'send_order_email' => false,
                'is_active' => true,
            ],
            [
                'currency' => 'GBP',
                'exchange_rate' => 0.0527032,
                'surcharge_percentage' => 5.0,
                'special_discount_percentage' => 0.0,
                'send_order_email' => true,
                'is_active' => true,
            ],
            [
                'currency' => 'EUR',
                'exchange_rate' => 0.0718710,
                'surcharge_percentage' => 5.0,
                'special_discount_percentage' => 2.0,
                'send_order_email' => false,
                'is_active' => true,
            ],
            [
                'currency' => 'KES',
                'exchange_rate' => 7.81498,
                'surcharge_percentage' => 2.5,
                'special_discount_percentage' => 0.0,
                'send_order_email' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $data) {
            Currency::updateOrCreate(
                ['currency' => $data['currency']],
                $data
            );
        }
    }
}