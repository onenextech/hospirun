<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

// use Illuminate\Contracts\Auth\Factory as Auth;

class UserCheck
{
  // protected $auth;
  /** When a user requests data with token, first check that user is active and unlocked.
   * User is locked after 5 times login with wrong password,
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    $user = Auth::user();
    if($user->status == 0) {
      return response('Inactive', 401);
    }
    if($user->login_attempt >= 5) {
      return response('Locked', 401);
    }
    return $next($request);
  }
}
