<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class AbilityRole extends BaseModel
{
    protected $table = 'ability_role';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id',
        'ability_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    public static function isAuthorized($ability_name, $role_id) {
        $abilityId = Ability::where('name', $ability_name)->first()->id;
        $abilityRole = AbilityRole::where('role_id', $role_id)->where('ability_id', $abilityId)->first();
        if ($abilityRole === null) {
            // not found
            return false;
        }
        return true;
    }

}
