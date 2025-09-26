<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'surcharge_percentage' => 'decimal:2',
        'special_discount_percentage' => 'decimal:2',
    ];

    /**
     * Get the foreign orders for the currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function foreignOrders()
    {
        return $this->hasMany(Order::class, 'foreign_currency_id');
    }
}
