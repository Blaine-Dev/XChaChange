<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'foreign_currency_id' => Currency::factory(),
            'originating_currency' => 'ZAR',
            'exchange_rate' => $this->faker->randomFloat(6, 0.01, 10),
            'foreign_amount' => $this->faker->randomFloat(2, 10, 1000),
            'originating_amount' => $this->faker->randomFloat(2, 100, 10000),
            'surcharge_percentage' => $this->faker->randomFloat(2, 0, 10),
            'surcharge_amount' => $this->faker->randomFloat(2, 0, 100),
            'total_amount' => $this->faker->randomFloat(2, 100, 11000),
            'special_discount_percentage' => $this->faker->randomFloat(2, 0, 5),
            'special_discount_amount' => $this->faker->randomFloat(2, 0, 50),
        ];
    }
}