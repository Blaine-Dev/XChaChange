<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'currency',
        'exchange_rate',
        'surcharge_percentage',
        'special_discount_percentage',
        'send_order_email',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'surcharge_percentage' => 'decimal:2',
        'special_discount_percentage' => 'decimal:2',
    ];

    public function foreignOrders()
    {
        return $this->hasMany(Order::class, 'foreign_currency_id');
    }
}
