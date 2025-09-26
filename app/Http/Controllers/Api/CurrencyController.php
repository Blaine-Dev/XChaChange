<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\Currency;

class CurrencyController extends Controller
{
    /**
     * Display all currencies.
     */
    public function index()
    {
        return Currency::all();
    }

    /**
     * Display active currencies.
     */
    public function list()
    {
        return Currency::where('is_active', true)->get([
            'id', 
            'currency', 
            'exchange_rate',
            'surcharge_percentage',
            'special_discount_percentage'
        ]);
    }

    /**
     * Display the source currency.
     */
    public function source()
    {
        return response()->json(['source' => config('services.currency.source')]);
    }

    /**
     * Display inactive currencies.
     */
    public function inactive()
    {
        return Currency::where('is_active', false)->get([
            'id', 
            'currency', 
            'exchange_rate',
            'surcharge_percentage',
            'special_discount_percentage'
        ]);
    }

    /**
     * Display a specific currency by its code.
     * 
     * @param string $currencyCode
     * @return \App\Models\Currency
     */
    public function show($currencyCode)
    {
        return Currency::where('currency', $currencyCode)->firstOrFail();
    }

    /**
     * Update a specific currency by its code.
     * 
     * @param string $currencyCode
     * @param string $field
     * @param mixed $value
     * @return \App\Models\Currency
     */
    public function update($currencyCode, $field, $value)
    {
        // Define allowed fields to validate against for security (prevents mass assignment vulnerabilities)
        $allowedFields = [
            'exchange_rate',
            'surcharge_percentage',
            'special_discount_percentage',
            'send_order_email',
            'is_active'
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        $currency = Currency::where('currency', $currencyCode)->firstOrFail();
        $currency->$field = $value;
        $currency->save();

        return response()->json([
            'message' => 'Currency updated successfully',
            'currency' => $currency
        ]);
    }

    /**
     * Update all currencies via API.
     */
    public function updateAll()
    {
        try {
            $sourceCurrency = config('services.currency.source');
            $currencies = config('services.currency.currencies');

            $response = Http::get(config('services.currency.base_url'), [
                'access_key' => config('services.currency.api_key'),
                'currencies' => implode(',', $currencies),
                'source' => $sourceCurrency,
                'format' => config('services.currency.format'),
            ]);

            if (!$response->ok()) {
                return response()->json(['message' => 'API request failed'], 500);
            }

            $data = $response->json();

            if (!isset($data['quotes'])) {
                return response()->json(['message' => 'No quotes found in API response'], 500);
            }

            $updatedCurrencies = [];

            // Process each currency rate from the API response
            foreach ($data['quotes'] as $key => $rate) {
                // Extract currency code by removing source currency prefix (e.g., 'USDEUR' -> 'EUR')
                $currencyCode = str_replace($sourceCurrency, '', $key);

                // Find existing currency or create new instance
                $currency = Currency::firstOrNew(['currency' => $currencyCode]);
                $currency->exchange_rate = $rate;

                // Set default values only for new currencies
                if (!$currency->exists) {
                    $currency->surcharge_percentage = 0;
                    $currency->special_discount_percentage = 0;
                    $currency->send_order_email = false;
                    $currency->is_active = true;
                }

                // Save the currency instance
                $currency->save();
                $updatedCurrencies[] = $currency;
            }

            return response()->json([
                'message' => 'Currencies updated successfully from API',
                'currencies' => $updatedCurrencies,
                'updated_count' => count($updatedCurrencies)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update currencies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a specific currency by its code.
     * 
     * @param string $currencyCode
     * @return \App\Models\Currency
     */
    public function activate($currencyCode)
    {
        $currency = Currency::where('currency', $currencyCode)->firstOrFail();
        $currency->is_active = true;
        $currency->save();

        return response()->json([
            'message' => 'Currency activated successfully',
            'currency' => $currency
        ]);
    }

    /**
     * Deactivate a specific currency by its code.
     * 
     * @param string $currencyCode
     * @return \App\Models\Currency
     */
    public function deactivate($currencyCode)
    {
        $currency = Currency::where('currency', $currencyCode)->firstOrFail();
        $currency->is_active = false;
        $currency->save();

        return response()->json([
            'message' => 'Currency deactivated successfully',
            'currency' => $currency
        ]);
    }

    /**
     * Enable send order email for a specific currency by its code.
     * 
     * @param string $currencyCode
     * @return \App\Models\Currency
     */
    public function enableSendOrderEmail($currencyCode)
    {
        $currency = Currency::where('currency', $currencyCode)->firstOrFail();
        $currency->send_order_email = true;
        $currency->save();

        return response()->json([
            'message' => 'Currency order email enabled successfully',
            'currency' => $currency
        ]);
    }

    /**
     * Disable send order email for a specific currency by its code.
     * 
     * @param string $currencyCode
     * @return \App\Models\Currency
     */
    public function disableSendOrderEmail($currencyCode)
    {
        $currency = Currency::where('currency', $currencyCode)->firstOrFail();
        $currency->send_order_email = false;
        $currency->save();

        return response()->json([
            'message' => 'Currency order email disabled successfully',
            'currency' => $currency
        ]);
    }
}
