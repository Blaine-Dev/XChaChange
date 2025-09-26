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
    public function index()
    {
        return Order::all();
    }

    public function show($id)
    {
        return Order::find($id);
    }

    public function showOrdersForUser($userId)
    {
        return Order::where('user_id', $userId)->get();
    }

    public function showOrdersForCurrency($currencyId)
    {
        return Order::where('foreign_currency_id', $currencyId)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'foreign_currency_id' => ['required', 'exists:currencies,id'],
            'foreign_amount' => ['nullable', 'numeric', 'gt:0'],
            'originating_amount' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $hasForeignAmount = isset($validated['foreign_amount']);
        $hasOriginatingAmount = isset($validated['originating_amount']);
        
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

        $currency = Currency::findOrFail($validated['foreign_currency_id']);
        $originatingCurrency = config('services.currency.source', 'ZAR');
        $exchangeRate = (float) $currency->exchange_rate;
        $surchargePct = (float) $currency->surcharge_percentage;
        $specialDiscountPct = (float) $currency->special_discount_percentage;

        if ($hasForeignAmount) {
            $foreignAmount = (float) $validated['foreign_amount'];
            $originatingAmount = $exchangeRate > 0 ? ($foreignAmount / $exchangeRate) : 0.0;
        } else {
            $originatingAmount = (float) $validated['originating_amount'];
            $foreignAmount = $originatingAmount * $exchangeRate;
        }

        $exchangeRate = round($exchangeRate, 8);
        $foreignAmount = round($foreignAmount, 2);
        $originatingAmount = round($originatingAmount, 2);
        $surchargeAmount = round($originatingAmount * ($surchargePct / 100.0), 2);
        $specialDiscountAmount = round($originatingAmount * ($specialDiscountPct / 100.0), 2);
        $totalAmount = round($originatingAmount + $surchargeAmount - $specialDiscountAmount, 2);

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

        $recipient = config('services.order_notifications.to');
        if ($recipient && $currency && $currency->send_order_email) {
            try {
                Mail::to($recipient)->send(new OrderPlaced($order));
            } catch (\Throwable $e) {
                // Silent fail
            }
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }
}