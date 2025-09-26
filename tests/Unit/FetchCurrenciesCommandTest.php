<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class FetchCurrenciesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock config values
        config([
            'services.currency.base_url' => 'https://api.currencylayer.com/live',
            'services.currency.api_key' => 'test-api-key',
            'services.currency.currencies' => ['USD', 'GBP', 'EUR'],
            'services.currency.source' => 'ZAR',
            'services.currency.format' => 1,
        ]);
    }

    /**
     * Test that the command fetches and updates currencies successfully.
     */
    public function test_command_fetches_and_updates_currencies_successfully()
    {
        // Mock successful API response
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZARUSD' => 0.0808279,
                    'ZARGBP' => 0.0527032,
                    'ZAREUR' => 0.0718710,
                ]
            ], 200)
        ]);

        // Run the command
        $exitCode = Artisan::call('currencies:fetch');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert currencies were created/updated
        $this->assertDatabaseHas('currencies', [
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('currencies', [
            'currency' => 'GBP',
            'exchange_rate' => 0.0527032,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('currencies', [
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'is_active' => true,
        ]);

        // Verify HTTP request was made with correct parameters
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://api.currencylayer.com/live') &&
                   $request->data()['access_key'] === 'test-api-key' &&
                   $request->data()['currencies'] === 'USD,GBP,EUR' &&
                   $request->data()['source'] === 'ZAR' &&
                   $request->data()['format'] === 1;
        });
    }

    /**
     * Test that the command updates existing currencies.
     */
    public function test_command_updates_existing_currencies()
    {
        // Create existing currency with different rate
        Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.075,
            'surcharge_percentage' => 7.50,
            'special_discount_percentage' => 0.00,
            'send_order_email' => false,
            'is_active' => true,
        ]);

        // Mock API response with updated rate
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZARUSD' => 0.0808279, // Updated rate
                ]
            ], 200)
        ]);

        Artisan::call('currencies:fetch');

        // Verify the exchange rate was updated but other fields preserved
        $currency = Currency::where('currency', 'USD')->first();
        $this->assertEquals(0.0808279, $currency->exchange_rate); // Decimal casting truncates to 6 places
        $this->assertEquals(7.50, $currency->surcharge_percentage); // Preserved
        $this->assertEquals(0.00, $currency->special_discount_percentage); // Preserved
        $this->assertFalse((bool) $currency->send_order_email); // Preserved
        $this->assertTrue((bool) $currency->is_active); // Preserved
    }

    /**
     * Test that the command preserves existing currency settings.
     */
    public function test_command_preserves_existing_currency_settings()
    {
        // Create existing currency with custom settings
        Currency::create([
            'currency' => 'GBP',
            'exchange_rate' => 0.05,
            'surcharge_percentage' => 5.00,
            'special_discount_percentage' => 2.00,
            'send_order_email' => true,
            'is_active' => false, // Inactive
        ]);

        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZARGBP' => 0.0527032,
                ]
            ], 200)
        ]);

        Artisan::call('currencies:fetch');

        $currency = Currency::where('currency', 'GBP')->first();
        
        // Exchange rate should be updated
        $this->assertEquals(0.0527032, $currency->exchange_rate);
        
        // Other settings should be preserved
        $this->assertEquals(5.00, $currency->surcharge_percentage);
        $this->assertEquals(2.00, $currency->special_discount_percentage);
        $this->assertTrue((bool) $currency->send_order_email);
        $this->assertFalse((bool) $currency->is_active); // Should remain inactive
    }

    /**
     * Test that the command sets default values for new currencies.
     */
    public function test_command_sets_default_values_for_new_currencies()
    {
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZAREUR' => 0.0718710,
                ]
            ], 200)
        ]);

        Artisan::call('currencies:fetch');

        $currency = Currency::where('currency', 'EUR')->first();
        
        $this->assertEquals(0.0718710, $currency->exchange_rate);
        $this->assertEquals(0.00, $currency->surcharge_percentage); // Default
        $this->assertEquals(0.00, $currency->special_discount_percentage); // Default
        $this->assertFalse((bool) $currency->send_order_email); // Default
        $this->assertTrue((bool) $currency->is_active); // Default
    }

    /**
     * Test that the command handles API request failure.
     */
    public function test_command_handles_api_request_failure()
    {
        // Mock failed API response
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([], 500)
        ]);

        $exitCode = Artisan::call('currencies:fetch');

        // Command should complete without throwing exception
        $this->assertEquals(0, $exitCode);

        // No currencies should be created
        $this->assertEquals(0, Currency::count());
    }

    /**
     * Test that the command handles API response without quotes.
     */
    public function test_command_handles_api_response_without_quotes()
    {
        // Mock API response without quotes
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => false,
                'error' => 'Invalid API key'
            ], 200)
        ]);

        $exitCode = Artisan::call('currencies:fetch');

        // Command should complete without throwing exception
        $this->assertEquals(0, $exitCode);

        // No currencies should be created
        $this->assertEquals(0, Currency::count());
    }

    /**
     * Test that the command strips source currency from quote keys.
     */
    public function test_command_strips_source_currency_from_quote_keys()
    {
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZARUSD' => 0.0808279, // Should become 'USD'
                    'ZARGBP' => 0.0527032, // Should become 'GBP'
                ]
            ], 200)
        ]);

        Artisan::call('currencies:fetch');

        // Verify currencies were created with correct codes (without ZAR prefix)
        $this->assertDatabaseHas('currencies', ['currency' => 'USD']);
        $this->assertDatabaseHas('currencies', ['currency' => 'GBP']);
        
        // Verify no currency with full quote key was created
        $this->assertDatabaseMissing('currencies', ['currency' => 'ZARUSD']);
        $this->assertDatabaseMissing('currencies', ['currency' => 'ZARGBP']);
    }

    /**
     * Test that the command output messages.
     */
    public function test_command_output_messages()
    {
        Http::fake([
            'https://api.currencylayer.com/live*' => Http::response([
                'success' => true,
                'quotes' => [
                    'ZARUSD' => 0.0808279,
                ]
            ], 200)
        ]);

        Artisan::call('currencies:fetch');

        // Check command output contains expected messages
        $output = Artisan::output();
        $this->assertStringContainsString('Fetching currency rates...', $output);
        $this->assertStringContainsString('Currencies table updated successfully!', $output);
    }

    /**
     * Test that the command handles network errors gracefully.
     */
    public function test_command_handles_network_errors_gracefully()
    {
        // Mock network error
        Http::fake([
            'https://api.currencylayer.com/live*' => function () {
                throw new \Exception('Network error');
            }
        ]);

        // Command should not throw exception
        $exitCode = Artisan::call('currencies:fetch');
        $this->assertEquals(0, $exitCode);
    }
}
