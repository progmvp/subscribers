<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscrCheckout extends Model
{
    protected $table = 'subscr_checkout';

    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'email',
        'plan_id',
        'payment_id',
        'amount',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];
}
