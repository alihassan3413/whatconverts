<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'account_id',
        'account',
        'profile_id',
        'profile',
        'lead_id',
        'lead_type',
        'lead_status',
        'date_created',
        'quotable',
        'quote_value',
        'sales_value',
        'lead_source',
        'lead_medium',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'quote_value' => 'decimal:2',
        'sales_value' => 'decimal:2',
    ];
}
