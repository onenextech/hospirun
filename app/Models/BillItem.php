<?php

namespace App\Models;

class BillItem extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bill_id',
        'item_id',
        'item_name',
        'unit_id',
        'unit_name',
        'charge',
        'quantity',
        'amount',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'deleted_at',
        'created_by',
        'deleted_by',
    ];

    public function item() {
        return $this->hasOne(Item::class,'id','item_id');
    }
    public function unit() {
        return $this->hasOne(Unit::class,'id','unit_id');
    }
}
