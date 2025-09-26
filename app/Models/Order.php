<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use SoftDeletes;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'foreign_currency_id',
        'originating_currency',
        'exchange_rate',
        'surcharge_percentage',
        'foreign_amount',
        'originating_amount',
        'surcharge_amount',
        'total_amount',
        'special_discount_percentage',
        'special_discount_amount',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'surcharge_percentage' => 'decimal:2',
        'foreign_amount' => 'decimal:2',
        'originating_amount' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'special_discount_percentage' => 'decimal:2',
        'special_discount_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function foreignCurrency()
    {
        return $this->belongsTo(Currency::class, 'foreign_currency_id');
    }
}