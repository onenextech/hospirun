<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{

  /**
    * @OA\Post(
    * path="/api/auth/register",
    * operationId="userRegister",
    * tags={"Auth"},
    * summary="User Register",
    *     @OA\RequestBody(
    *         @OA\JsonContent(),
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"name", "password", "confirm_password"},
    *               @OA\Property(property="name", type="text"),
    *               @OA\Property(property="email", type="text"),
    *               @OA\Property(property="password", type="password"),
    *               @OA\Property(property="confirm_password", type="password")
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=201,
    *          description="Success"
    *       ),
    * )
  */
  public function register(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'password' => 'required',
      'confirm_password' => 'required|same:password',
    ]);

    if($validator->fails()){
      return $this->response('not_valid', $validator->errors());
    }

    $input = $request->only(['name', 'email', 'password']);
    $input['password'] = bcrypt($input['password']);
    $user = User::create($input);
    $result['token'] =  $user->createToken('DohEainPortal')->plainTextToken;
    $result['name'] =  $user->name;

    return $this->response('created', $result);
  }

  /**
    * @OA\Post(
    * path="/api/auth/login",
    * operationId="userLogin",
    * tags={"Auth"},
    * summary="User Login",
    *     @OA\RequestBody(
    *         @OA\JsonContent(),
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"name", "password"},
    *               @OA\Property(property="name", type="text"),
    *               @OA\Property(property="password", type="password")
    *            ),
    *        ),
    *    ),
    *      @OA\Response(
    *          response=200,
    *          description="Success"
    *       ),
    * )
  */
  public function login(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string',
      'password' => 'required|string',
    ]);

    if($validator->fails()){
      return $this->response('not_valid', $validator->errors());
    }

    if(Auth::attempt(['name' => $request->name, 'password' => $request->password])){
      $authUser = Auth::user();
      if($authUser->status == 0) {
        return $this->response('inactive');
      }
      if($authUser->login_attempt >= 5) {
        return $this->response('locked');
      }
      $userData = User::with('role.abilities')->find(Auth::user()->id)->only(['name', 'fullname', 'email', 'role_id', 'role', 'profile_image']);

      //get s3 public url for profile_image
      $file = 'profile_images/' . $userData['profile_image'];
      $url = config('filesystems.disks.s3.url') . $file;
      $userData['profile_image'] = $url;

      $result['token'] =  $authUser->createToken('DohEainPortal')->plainTextToken;
      $result['user_data'] = $userData;
      return $this->response('done', $result);
    }
    else{
      return $this->response('logout');
    }
  }

  /**
    * @OA\Get(
    * path="/api/auth/me",
    * operationId="userInfo",
    * tags={"Auth"},
    * summary="User Info",
    *      @OA\Response(
    *          response=200,
    *          description="Success"
    *       ),
    * )
  */
  public function user(Request $request)
  {
    $user = $request->user();
    $result = User::with('role.abilities')->find($user->id)->only(['id', 'name', 'fullname', 'role', 'email', 'profile_image']);
    return $this->response('done', $result);
  }

  /**
      * @OA\Post(
      * path="/api/auth/logout",
      * operationId="userLogout",
      * tags={"Auth"},
      * summary="User Logout",
      *      @OA\Response(
      *          response=200,
      *          description="Success"
      *       ),
      * )
  */
  public function logout(Request $request)
  {
    // Revoke the token that was used to authenticate the current request...
    $request->user()->currentAccessToken()->delete();
    // $user = request()->user();
    // $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
    return $this->response('done');
  }
}
