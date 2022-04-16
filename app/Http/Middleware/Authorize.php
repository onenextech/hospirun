<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Ability;
use App\Models\AbilityRole;

// use Illuminate\Contracts\Auth\Factory as Auth;

class Authorize
{
    // protected $auth;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // this is for handling authorization for route
        // get all params except first two params
        $ability = array_slice(func_get_args(), 2)[0];

        $abilityId = Ability::where('name', $ability)->first()->id;
        $roleId = Auth::user()->role_id;

        $abilityRole = AbilityRole::where('role_id', $roleId)->where('ability_id', $abilityId)->first();
        if ($abilityRole === null) {
            // not found
            return response(['message'=>'Unauthorized'], 401);
        }

        return $next($request);
    }
}
