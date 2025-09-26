<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'currency' => $this->faker->unique()->currencyCode(),
            'exchange_rate' => $this->faker->randomFloat(6, 0.001, 1),
            'surcharge_percentage' => $this->faker->randomFloat(2, 0, 10),
            'special_discount_percentage' => $this->faker->randomFloat(2, 0, 5),
            'send_order_email' => $this->faker->boolean(),
            'is_active' => true,
        ];
    }
}