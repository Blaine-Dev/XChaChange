<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;

class OrderController extends Controller
{
    /**
     * Display all orders.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return Order::all();
    }

    /**
     * Display a specific order by its ID.
     * 
     * @param int $id
     * @return \App\Models\Order|null
     */
    public function show($id)
    {
        return Order::find($id);
    }

    /**
     * Display all orders for a specific user.
     * 
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function showOrdersForUser($userId)
    {
        return Order::where('user_id', $userId)->get();
    }

    /**
     * Display all orders for a specific currency.
     * 
     * @param int $currencyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function showOrdersForCurrency($currencyId)
    {
        return Order::where('foreign_currency_id', $currencyId)->get();
    }

    /**
     * Create a new currency exchange order.
     * Accepts either foreign_amount OR originating_amount, calculates the other.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'foreign_currency_id' => ['required', 'exists:currencies,id'],
            'foreign_amount' => ['nullable', 'numeric', 'gt:0'],
            'originating_amount' => ['nullable', 'numeric', 'gt:0'],
        ]);

        // Check which amount type was provided
        $hasForeignAmount = isset($validated['foreign_amount']);
        $hasOriginatingAmount = isset($validated['originating_amount']);
        
        // Ensure exactly one amount type is provided
        if (!$hasForeignAmount && !$hasOriginatingAmount) {
            return response()->json([
                'message' => 'Either foreign_amount or originating_amount must be provided'
            ], 422);
        }
        
        if ($hasForeignAmount && $hasOriginatingAmount) {
            return response()->json([
                'message' => 'Provide either foreign_amount or originating_amount, not both'
            ], 422);
        }

        // Get currency details and exchange rate information
        $currency = Currency::findOrFail($validated['foreign_currency_id']);
        $originatingCurrency = config('services.currency.source', 'ZAR');
        $exchangeRate = (float) $currency->exchange_rate;
        $surchargePct = (float) $currency->surcharge_percentage;
        $specialDiscountPct = (float) $currency->special_discount_percentage;

        // Calculate the missing amount based on which was provided
        if ($hasForeignAmount) {
            // Calculate originating amount from foreign amount
            $foreignAmount = (float) $validated['foreign_amount'];
            $originatingAmount = $exchangeRate > 0 ? ($foreignAmount / $exchangeRate) : 0.0;
        } else {
            // Calculate foreign amount from originating amount
            $originatingAmount = (float) $validated['originating_amount'];
            $foreignAmount = $originatingAmount * $exchangeRate;
        }

        // Round all monetary values to appropriate precision
        $exchangeRate = round($exchangeRate, 8);
        $foreignAmount = round($foreignAmount, 2);
        $originatingAmount = round($originatingAmount, 2);
        
        // Calculate surcharge and discount amounts
        $surchargeAmount = round($originatingAmount * ($surchargePct / 100.0), 2);
        $specialDiscountAmount = round($originatingAmount * ($specialDiscountPct / 100.0), 2);
        $totalAmount = round($originatingAmount + $surchargeAmount - $specialDiscountAmount, 2);

        // Create the order with all calculated values
        $order = Order::create([
            'user_id' => $validated['user_id'],
            'foreign_currency_id' => $validated['foreign_currency_id'],
            'originating_currency' => $originatingCurrency,
            'exchange_rate' => $exchangeRate,
            'surcharge_percentage' => $surchargePct,
            'foreign_amount' => $foreignAmount,
            'originating_amount' => $originatingAmount,
            'surcharge_amount' => $surchargeAmount,
            'total_amount' => $totalAmount,
            'special_discount_percentage' => $specialDiscountPct,
            'special_discount_amount' => $specialDiscountAmount,
        ]);

        // Send order notification email if configured and currency allows it
        $recipient = config('services.order_notifications.to');
        if ($recipient && $currency && $currency->send_order_email) {
            try {
                Mail::to($recipient)->send(new OrderPlaced($order));
            } catch (\Throwable $e) {
                // Silent fail - don't let email issues prevent order creation
            }
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }
}