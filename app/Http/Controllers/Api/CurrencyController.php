<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function index()
    {
        return Currency::all();
    }

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

    public function source()
    {
        return response()->json(['source' => config('services.currency.source')]);
    }

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

    public function show($currencyCode)
    {
        return Currency::where('currency', $currencyCode)->firstOrFail();
    }

    public function update($currencyCode, $field, $value)
    {
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

            foreach ($data['quotes'] as $key => $rate) {
                $currencyCode = str_replace($sourceCurrency, '', $key);

                $currency = Currency::firstOrNew(['currency' => $currencyCode]);
                $currency->exchange_rate = $rate;

                if (!$currency->exists) {
                    $currency->surcharge_percentage = 0;
                    $currency->special_discount_percentage = 0;
                    $currency->send_order_email = false;
                    $currency->is_active = true;
                }

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
