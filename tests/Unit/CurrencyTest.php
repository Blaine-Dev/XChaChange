<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the currency has the expected fillable attributes.
     */
    public function test_currency_has_fillable_attributes()
    {
        $fillable = [
            'currency',
            'exchange_rate',
            'surcharge_percentage',
            'special_discount_percentage',
            'send_order_email',
            'is_active',
        ];

        $currency = new Currency();
        $this->assertEquals($fillable, $currency->getFillable());
    }

    /**
     * Test that the currency can be created with valid data.
     */
    public function test_currency_can_be_created_with_valid_data()
    {
        $currencyData = [
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
            'special_discount_percentage' => 0.0,
            'send_order_email' => false,
            'is_active' => true,
        ];

        $currency = Currency::create($currencyData);

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertEquals('USD', $currency->currency);
        $this->assertEquals(0.0808279, $currency->exchange_rate);
        $this->assertEquals(7.5, $currency->surcharge_percentage);
        $this->assertEquals(0.0, $currency->special_discount_percentage);
        $this->assertFalse($currency->send_order_email);
        $this->assertTrue($currency->is_active);
    }

    /**
     * Test that the currency has the expected foreign orders relationship.
     */
    public function test_currency_has_foreign_orders_relationship()
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
            'foreign_amount' => 100.00,
            'originating_amount' => 1897.47,
            'surcharge_percentage' => 5.0,
            'surcharge_amount' => 94.87,
            'total_amount' => 1992.34,
            'special_discount_percentage' => 0.0,
            'special_discount_amount' => 0.0,
        ]);

        $this->assertTrue($currency->foreignOrders()->exists());
        $this->assertEquals(1, $currency->foreignOrders()->count());
        $this->assertEquals($order->id, $currency->foreignOrders()->first()->id);
    }

    /**
     * Test that the currency defaults to active.
     */
    public function test_currency_defaults_to_active()
    {
        $currency = Currency::create([
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'surcharge_percentage' => 5.0,
        ]);

        $currency->refresh();

        $this->assertTrue((bool) $currency->is_active);
    }

    /**
     * Test that the currency code is unique.
     */
    public function test_currency_code_is_unique()
    {
        Currency::create([
            'currency' => 'KES',
            'exchange_rate' => 7.81498,
            'surcharge_percentage' => 2.5,
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Currency::create([
            'currency' => 'KES',
            'exchange_rate' => 8.0,
            'surcharge_percentage' => 3.0,
            'is_active' => true,
        ]);
    }

    /**
     * Test that the currency can be updated.
     */
    public function test_currency_can_be_updated()
    {
        $currency = Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.08,
            'surcharge_percentage' => 7.0,
            'is_active' => true,
        ]);

        $currency->update([
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
        ]);

        $this->assertEquals(0.0808279, $currency->fresh()->exchange_rate);
        $this->assertEquals(7.50, $currency->fresh()->surcharge_percentage);
    }

    /**
     * Test that the currency can be deactivated.
     */
    public function test_currency_can_be_deactivated()
    {
        $currency = Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
            'is_active' => true,
        ]);

        $currency->update(['is_active' => false]);

        $this->assertFalse((bool) $currency->fresh()->is_active);
    }
}
