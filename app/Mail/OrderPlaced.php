<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public ?Currency $currency;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->currency = Currency::find($order->foreign_currency_id);
    }

    public function build()
    {
        $code = $this->currency?->currency ?? 'N/A';

        return $this->subject('New Order Placed - ' . $code . ' #' . $this->order->id)
            ->view('emails.order_placed', [
                'order' => $this->order,
                'currency' => $this->currency,
            ]);
    }
}