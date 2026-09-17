<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'item_code',
        'name',
        'category',
        'description',
        'status',
        'registered_at',
        'unregistered_at',
        'unregistration_reason',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'unregistered_at' => 'datetime',
    ];
}
