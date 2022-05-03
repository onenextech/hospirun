<?php

namespace App\Models;

class DailyClosing extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'opening_balance',
        'deposit_total',
        'bill_total',
        'grand_total',
        'actual_amount',
        'adjusted_amount',
        'remark',
    ];

    protected $casts = [
        'opening_balance' => 'double',
        'deposit_total' => 'double',
        'bill_total' => 'double',
        'grand_total' => 'double',
        'actual_amount' => 'double',
        'adjusted_amount' => 'double',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

}
