<?php

namespace App\Models;

class Payment extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'patient_id',
        'patient_name',
        'patient_phone',
        'patient_address',
        'amount',
        'remark',
        'subject',
        'bill_id',
    ];

    protected $casts = [
        'amount' => 'double',
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

    public function bill() {
        return $this->hasOne(Bill::class,'id','bill_id');
    }
    public function unit() {
        return $this->hasOne(Unit::class,'id','unit_id');
    }
}
