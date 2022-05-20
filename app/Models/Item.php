<?php

namespace App\Models;

class Item extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'category_name',
        'unit_id',
        'unit_name',
        'charge',
        'type',
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

    public function category() {
        return $this->hasOne(Category::class,'id','category_id')->select('id', 'name');
    }
    public function unit() {
        return $this->hasOne(Unit::class,'id','unit_id')->select('id', 'name');
    }
}
