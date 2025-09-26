<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = false;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test currencies
        Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
            'special_discount_percentage' => 0.0,
            'send_order_email' => false,
            'is_active' => true,
        ]);

        Currency::create([
            'currency' => 'GBP',
            'exchange_rate' => 0.0527032,
            'surcharge_percentage' => 5.0,
            'special_discount_percentage' => 0.0,
            'send_order_email' => true,
            'is_active' => true,
        ]);

        Currency::create([
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'surcharge_percentage' => 5.0,
            'special_discount_percentage' => 2.0,
            'send_order_email' => false,
            'is_active' => false, // Inactive currency
        ]);
    }

    /**
     * Test that the API returns all currencies.
     */
    public function test_can_get_all_currencies()
    {
        $response = $this->getJson('/api/currencies');

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    /**
     * Test that the API returns all currencies.
     */
    public function test_can_get_currency_list_for_selection()
    {
        $response = $this->getJson('/api/currencies/list');

        $response->assertStatus(200)
                ->assertJsonCount(2); // Only active currencies

        $currencies = $response->json();
        foreach ($currencies as $currency) {
            $this->assertNotNull($currency['exchange_rate']);
        }
    }

    /**
     * Test that the API returns all inactive currencies.
     */
    public function test_can_get_inactive_currencies_only()
    {
        $response = $this->getJson('/api/currencies/inactive');

        $response->assertStatus(200)
                ->assertJsonCount(1); // Only EUR is inactive
    }

    /**
     * Test that the API returns the source currency.
     */
    public function test_can_get_source_currency()
    {
        // Mock the config value
        config(['services.currency.source' => 'ZAR']);

        $response = $this->getJson('/api/currencies/source');

        $response->assertStatus(200)
                ->assertJson(['source' => 'ZAR']);
    }

    /**
     * Test that the API returns a specific currency.
     */
    public function test_can_show_specific_currency()
    {
        $response = $this->getJson('/api/currencies/USD');

        $response->assertStatus(200)
                ->assertJson([
                    'currency' => 'USD',
                    'exchange_rate' => 0.0808279,
                    'surcharge_percentage' => 7.5,
                    'is_active' => true,
                ]);
    }

    /**
     * Test that the API returns a specific currency.
     */
    public function test_returns_404_for_nonexistent_currency()
    {
        $response = $this->getJson('/api/currencies/XXX');

        $response->assertStatus(404);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_update_currency_field()
    {
        $response = $this->postJson('/api/currencies/update/USD/exchange_rate/0.085');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Currency updated successfully']);

        // Verify the update in database
        $currency = Currency::where('currency', 'USD')->first();
        $this->assertEquals(0.085, $currency->exchange_rate);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_update_surcharge_percentage()
    {
        $response = $this->postJson('/api/currencies/update/USD/surcharge_percentage/8.0');

        $response->assertStatus(200);

        $currency = Currency::where('currency', 'USD')->first();
        $this->assertEquals(8.0, $currency->surcharge_percentage);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_update_special_discount_percentage()
    {
        $response = $this->postJson('/api/currencies/update/USD/special_discount_percentage/1.5');

        $response->assertStatus(200);

        $currency = Currency::where('currency', 'USD')->first();
        $this->assertEquals(1.5, $currency->special_discount_percentage);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_update_fails_for_nonexistent_currency()
    {
        $response = $this->postJson('/api/currencies/update/XXX/exchange_rate/0.085');

        $response->assertStatus(404);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_update_fails_for_invalid_field()
    {
        $response = $this->postJson('/api/currencies/update/USD/invalid_field/123');

        $response->assertStatus(400)
                ->assertJson(['error' => 'Invalid field']);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_activate_currency()
    {
        // First ensure EUR is inactive
        $eur = Currency::where('currency', 'EUR')->first();
        $eur->update(['is_active' => false]);

        $response = $this->postJson('/api/currencies/activate/EUR');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Currency activated successfully']);

        $currency = Currency::where('currency', 'EUR')->first();
        $this->assertTrue((bool) $currency->is_active);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_deactivate_currency()
    {
        $response = $this->postJson('/api/currencies/deactivate/USD');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Currency deactivated successfully']);

        $currency = Currency::where('currency', 'USD')->first();
        $this->assertFalse((bool) $currency->is_active);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_enable_send_order_email()
    {
        $response = $this->postJson('/api/currencies/enableSendOrderEmail/USD');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Currency order email enabled successfully']);

        $currency = Currency::where('currency', 'USD')->first();
        $this->assertTrue((bool) $currency->send_order_email);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_can_disable_send_order_email()
    {
        // First ensure GBP has email enabled
        $gbp = Currency::where('currency', 'GBP')->first();
        $gbp->update(['send_order_email' => true]);

        $response = $this->postJson('/api/currencies/disableSendOrderEmail/GBP');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Currency order email disabled successfully']);

        $currency = Currency::where('currency', 'GBP')->first();
        $this->assertFalse((bool) $currency->send_order_email);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_bulk_update_all_currencies()
    {
        config([
            'services.currency.base_url' => 'https://api.currencylayer.com/live',
            'services.currency.api_key' => 'test-api-key',
            'services.currency.currencies' => ['USD', 'GBP', 'EUR'],
            'services.currency.source' => 'ZAR',
            'services.currency.format' => 1,
        ]);

        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZARUSD' => 0.09,
                    'ZARGBP' => 0.055,
                    'ZAREUR' => 0.075,
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/currencies/updateAll');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Currencies updated successfully from API']);

        $usd = Currency::where('currency', 'USD')->first();
        $this->assertEquals(0.09, $usd->exchange_rate);
        $this->assertEquals(7.5, $usd->surcharge_percentage);

        $gbp = Currency::where('currency', 'GBP')->first();
        $this->assertEquals(0.055, $gbp->exchange_rate);
        $this->assertEquals(5.0, $gbp->surcharge_percentage);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_bulk_update_handles_api_failure()
    {
        config([
            'services.currency.base_url' => 'https://api.currencylayer.com/live',
            'services.currency.api_key' => 'test-api-key',
            'services.currency.currencies' => ['USD', 'GBP', 'EUR'],
            'services.currency.source' => 'ZAR',
            'services.currency.format' => 1,
        ]);

        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([], 500)
        ]);

        $response = $this->postJson('/api/currencies/updateAll');

        $response->assertStatus(500)
                ->assertJson(['message' => 'API request failed']);
    }

    /**
     * Test that the API updates a currency field.
     */
    public function test_currency_route_validates_currency_code_format()
    {
        $response = $this->getJson('/api/currencies/US');

        $response->assertStatus(404);

        $response = $this->getJson('/api/currencies/USDD');

        $response->assertStatus(404);
    }
}
