<?php

namespace App\Models;

class Bill extends BaseModel
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
        'total_amount',
        'remark',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'double',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function patient() {
        return $this->hasOne(Patient::class,'id','patient_id');
    }

    public function bill_items() {
        return $this->hasMany(BillItem::class,'bill_id','id');
    }

    public function payment() {
        return $this->hasOne(Payment::class,'bill_id','id');
    }
}
