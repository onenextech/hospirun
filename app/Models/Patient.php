<?php

namespace App\Models;

class Patient extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cpi_number',
        'name',
        'gender',
        'date_of_birth',
        'age',
        'address',
        'phone',
        'blood_group',
        'nrc_number',
        'credit_balance',
        'status',
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
