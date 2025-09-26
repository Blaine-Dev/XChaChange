<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Order Placed</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2>New Order Placed</h2>

    <p><strong>Order ID:</strong> {{ $order->id }}</p>
    <p><strong>User ID:</strong> {{ $order->user_id }}</p>
    <p><strong>Date:</strong> {{ $order->created_at }}</p>

    <hr>

    <p><strong>Foreign Currency:</strong> {{ $currency?->currency ?? '#' }}</p>
    <p><strong>Foreign Amount:</strong> {{ number_format($order->foreign_amount, 2) }}</p>
    <p><strong>Exchange Rate:</strong> {{ number_format($order->exchange_rate, 6) }}</p>

    <p><strong>Originating Currency:</strong> {{ $order->originating_currency }}</p>
    <p><strong>Originating Amount:</strong> {{ number_format($order->originating_amount, 2) }}</p>

    <p><strong>Surcharge %:</strong> {{ number_format($order->surcharge_percentage ?? 0, 2) }}%</p>
    <p><strong>Surcharge Amount:</strong> {{ number_format($order->surcharge_amount ?? 0, 2) }}</p>

    <p><strong>Special Discount % (not applied):</strong> {{ number_format($order->special_discount_percentage ?? 0, 2) }}%</p>
    <p><strong>Special Discount Amount (not applied):</strong> {{ number_format($order->special_discount_amount ?? 0, 2) }}</p>

    <p><strong>Total Amount:</strong> {{ number_format($order->total_amount, 2) }}</p>

    <hr>
    <p style="font-size: 12px; color: #555;">This is an automated message.</p>
</body>
</html>