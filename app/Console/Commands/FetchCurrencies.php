<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Currency;

class FetchCurrencies extends Command
{
    protected $signature = 'currencies:fetch';
    protected $description = 'Fetch currency rates from API and update currencies table';

    public function handle(): void
    {
        $this->info('Fetching currency rates...');

        try {
            $response = Http::get(config('services.currency.base_url'), [
                'access_key' => config('services.currency.api_key'),
                'currencies' => implode(',', config('services.currency.currencies')),
                'source' => config('services.currency.source'),
                'format' => config('services.currency.format'),
            ]);

            if (!$response->ok()) {
                $this->error('API request failed');
                return;
            }

            $data = $response->json();

            if (!isset($data['quotes'])) {
                $this->error('No quotes found in API response');
                return;
            }

            $sourceCurrency = config('services.currency.source');

            foreach ($data['quotes'] as $key => $rate) {
                $currencyCode = str_replace($sourceCurrency, '', $key);

                $currency = Currency::firstOrNew(['currency' => $currencyCode]);
                $currency->exchange_rate = $rate;

                $currency->surcharge_percentage = $currency->surcharge_percentage ?? 0;
                $currency->special_discount_percentage = $currency->special_discount_percentage ?? 0;
                $currency->send_order_email = $currency->send_order_email ?? false;
                $currency->is_active = $currency->is_active ?? true;

                $currency->save();
            }

            $this->info('Currencies table updated successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to fetch currency rates: ' . $e->getMessage());
        }
    }
}