<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_has_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'foreign_currency_id',
            'originating_currency',
            'exchange_rate',
            'surcharge_percentage',
            'foreign_amount',
            'originating_amount',
            'surcharge_amount',
            'total_amount',
            'special_discount_percentage',
            'special_discount_amount',
        ];

        $order = new Order();
        $this->assertEquals($fillable, $order->getFillable());
    }

    public function test_order_can_be_created_with_valid_data()
    {
        $user = User::factory()->create();
        $currency = Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
            'is_active' => true,
        ]);

        $orderData = [
            'user_id' => $user->id,
            'foreign_currency_id' => $currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 0.0808279,
            'foreign_amount' => 100.00,
            'originating_amount' => 1237.50,
            'surcharge_percentage' => 7.5,
            'surcharge_amount' => 92.81,
            'total_amount' => 1330.31,
            'special_discount_percentage' => 0.0,
            'special_discount_amount' => 0.0,
        ];

        $order = Order::create($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals($currency->id, $order->foreign_currency_id);
        $this->assertEquals('ZAR', $order->originating_currency);
        $this->assertEquals(0.0808279, $order->exchange_rate);
        $this->assertEquals(100.00, $order->foreign_amount);
        $this->assertEquals(1237.50, $order->originating_amount);
        $this->assertEquals(7.5, $order->surcharge_percentage);
        $this->assertEquals(92.81, $order->surcharge_amount);
        $this->assertEquals(1330.31, $order->total_amount);
    }

    public function test_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $currency = Currency::create([
            'currency' => 'GBP',
            'exchange_rate' => 0.0527032,
            'surcharge_percentage' => 5.0,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'foreign_currency_id' => $currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 0.0527032,
            'foreign_amount' => 50.00,
            'originating_amount' => 948.74,
            'surcharge_percentage' => 5.0,
            'surcharge_amount' => 47.44,
            'total_amount' => 996.18,
            'special_discount_percentage' => 0.0,
            'special_discount_amount' => 0.0,
        ]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
        $this->assertEquals($user->name, $order->user->name);
    }

    public function test_order_belongs_to_foreign_currency()
    {
        $user = User::factory()->create();
        $currency = Currency::create([
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'surcharge_percentage' => 5.0,
            'special_discount_percentage' => 2.0,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'foreign_currency_id' => $currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 0.0718710,
            'foreign_amount' => 75.00,
            'originating_amount' => 1043.61,
            'surcharge_percentage' => 5.0,
            'surcharge_amount' => 52.18,
            'total_amount' => 1095.79,
            'special_discount_percentage' => 2.0,
            'special_discount_amount' => 20.87,
        ]);

        $this->assertInstanceOf(Currency::class, $order->foreignCurrency);
        $this->assertEquals($currency->id, $order->foreignCurrency->id);
        $this->assertEquals('EUR', $order->foreignCurrency->currency);
    }

    public function test_order_uses_soft_deletes()
    {
        $user = User::factory()->create();
        $currency = Currency::create([
            'currency' => 'KES',
            'exchange_rate' => 7.81498,
            'surcharge_percentage' => 2.5,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'foreign_currency_id' => $currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 7.81498,
            'foreign_amount' => 1000.00,
            'originating_amount' => 127.96,
            'surcharge_percentage' => 2.5,
            'surcharge_amount' => 3.20,
            'total_amount' => 131.16,
            'special_discount_percentage' => 0.0,
            'special_discount_amount' => 0.0,
        ]);

        $orderId = $order->id;

        // Soft delete the order
        $order->delete();

        // Order should not be found in normal queries
        $this->assertNull(Order::find($orderId));

        // But should be found when including trashed
        $this->assertNotNull(Order::withTrashed()->find($orderId));
        $this->assertTrue(Order::withTrashed()->find($orderId)->trashed());
    }

    public function test_order_calculations_are_consistent()
    {
        $user = User::factory()->create();
        $currency = Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
            'is_active' => true,
        ]);

        // Test with known values
        $originatingAmount = 1000.00;
        $surchargePercentage = 7.5;
        $expectedSurchargeAmount = round($originatingAmount * ($surchargePercentage / 100), 2);
        $expectedTotalAmount = round($originatingAmount + $expectedSurchargeAmount, 2);

        $order = Order::create([
            'user_id' => $user->id,
            'foreign_currency_id' => $currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 0.0808279,
            'foreign_amount' => round($originatingAmount * 0.0808279, 2),
            'originating_amount' => $originatingAmount,
            'surcharge_percentage' => $surchargePercentage,
            'surcharge_amount' => $expectedSurchargeAmount,
            'total_amount' => $expectedTotalAmount,
            'special_discount_percentage' => 0.0,
            'special_discount_amount' => 0.0,
        ]);

        $this->assertEquals($expectedSurchargeAmount, $order->surcharge_amount);
        $this->assertEquals($expectedTotalAmount, $order->total_amount);
        $this->assertEquals(75.0, $order->surcharge_amount); // 7.5% of 1000
        $this->assertEquals(1075.0, $order->total_amount); // 1000 + 75
    }

    public function test_order_with_special_discount()
    {
        $user = User::factory()->create();
        $currency = Currency::create([
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'surcharge_percentage' => 5.0,
            'special_discount_percentage' => 2.0,
            'is_active' => true,
        ]);

        $originatingAmount = 1000.00;
        $specialDiscountPercentage = 2.0;
        $expectedSpecialDiscountAmount = round($originatingAmount * ($specialDiscountPercentage / 100), 2);

        $order = Order::create([
            'user_id' => $user->id,
            'foreign_currency_id' => $currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 0.0718710,
            'foreign_amount' => 71.87,
            'originating_amount' => $originatingAmount,
            'surcharge_percentage' => 5.0,
            'surcharge_amount' => 50.0,
            'total_amount' => 1050.0,
            'special_discount_percentage' => $specialDiscountPercentage,
            'special_discount_amount' => $expectedSpecialDiscountAmount,
        ]);

        $this->assertEquals(20.0, $order->special_discount_amount); // 2% of 1000
        $this->assertEquals(2.0, $order->special_discount_percentage);
    }
}
