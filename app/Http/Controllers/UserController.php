<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{

    /** Get User List
        * @OA\Get(
        * path="/api/users",
        * operationId="userList",
        * tags={"User"},
        * summary="List",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"per_page", "page", "sort_by"},
        *               @OA\Property(property="q", type="string"),
        *               @OA\Property(property="per_page", type="integer"),
        *               @OA\Property(property="page", type="integer"),
        *               @OA\Property(property="sort_by", type="string"),
        *               @OA\Property(property="sort_desc", type="boolean"),
        *               @OA\Property(property="role_id", type="integer"),
        *               @OA\Property(property="status", type="integer")
        *            ),
        *        ),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function all(Request $request)
    {
        $total = 0;
        $user = null;

        // validate incoming request
        $this->validate($request, [
            'per_page' => 'required|integer',
            'page' => 'required|integer',
            'sort_by' => 'required|string'
        ]);

        $q = $request->input('q');
        $perPage = $request->input('per_page');
        $page = $request->input('page');
        $sortBy = $request->input('sort_by');

        $sortDesc = $request->input('sort_desc');
        if($sortDesc == "true") {
            $sortOrder = "desc";
        }
        else {
            $sortOrder = "asc";
        }

        // filters
        $roleId = $request->input('role_id');
        $status = $request->input('status');

        $skip = ($page-1) * $perPage;

        $user = User::with('role')
        ->where(function ($query) use($status) {
            if($status !== null) {
                $query->where('status', $status);
            }
        })
        ->where(function ($query) use($roleId) {
            if($roleId !== null) {
                $query->where('role_id', $roleId);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('name', 'like', "%{$q}%");
            }
        })
        ->orderBy($sortBy, $sortOrder)
        ->skip($skip)->take($perPage)
        ->get();

        $total = User::where(function ($query) use($roleId) {
            if($roleId !== null) {
                $query->where('role_id', $roleId);
            }
        })
        ->where(function ($query) use($status) {
            if($status !== null) {
                $query->where('status', $status);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('name', 'like', "%{$q}%");
            }
        })
        ->get()->count();

        $data['users'] = $user;
        $data['total'] = $total;

        if(!count($data)){
            return $this->response('no_data');
        }
        return $this->response('done', $data);
    }

    /** Get User
        * @OA\Get(
        * path="/api/users/{id}",
        * operationId="userGet",
        * tags={"User"},
        * summary="Get",
        *     @OA\Parameter(
        *       in="path",
        *       name="id",
        *       required=true,
        *       @OA\Schema(type="integer"),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function get($id)
    {
        $data = User::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        $data['profile_image'] = config('filesystems.disks.s3.url') . 'profile_images/'. $data['profile_image'];
        return $this->response('done', $data);
    }

    /** Create User
        * @OA\Post(
        * path="/api/users",
        * operationId="userCreate",
        * tags={"User"},
        * summary="Create",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"name", "password", "fullname", "role_id"},
        *               @OA\Property(property="name", type="string"),
        *               @OA\Property(property="password", type="string"),
        *               @OA\Property(property="fullname", type="string"),
        *               @OA\Property(property="role_id", type="integer"),
        *               @OA\Property(property="email", type="string"),
        *               @OA\Property(property="status", type="integer")
        *            ),
        *        ),
        *     ),
        *     @OA\Response(
        *          response=201,
        *          description="Success"
        *     ),
        * )
    */
    public function add(Request $request)
    {
        //validate incoming request - FormData
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'fullname' => 'required|string',
            'role_id' => 'required|numeric|min:1',
            'email' => 'email:rfc,dns',
            'status' => 'numeric',
            // 'profile_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|nullable',
        ]);

        try {
            $data = $request->only(['name', 'password', 'fullname', 'role_id', 'email', 'status']);
            if($request->hasfile('profile_image'))
            {
                $file = $request->file('profile_image');
                $imageName = Str::uuid() . '.' . $file->extension();

                $img = Image::make($file->path());
                $img->resize(128, 128, function ($const) {
                    $const->aspectRatio();
                });

                $resource = $img->stream()->detach();
                Storage::put('profile_images/'.$imageName, $resource);

                $data['profile_image'] =  $imageName;
            }
            $data['password'] = bcrypt($data['password']);
            $data['created_by'] = Auth::user()->name; // track who is creating this
            $result = User::insertGetId($data);
            $data = array('id'=> $result) + $data; //add generated id infront of response data array
            unset($data['password']); // remove password for responding created user data
        } catch (\Exception $e) {
        //return error message
        return $this->response('not_valid', $e);
        }

        //return successful response
        return $this->response('created', $data);
    }

    /** Update User
        * @OA\Put(
        * path="/api/users/{id}",
        * operationId="userUpdate",
        * tags={"User"},
        * summary="Update",
        *     @OA\Parameter(
        *       in="path",
        *       name="id",
        *       required=true,
        *       @OA\Schema(type="integer"),
        *     ),
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               @OA\Property(property="password", type="string"),
        *               @OA\Property(property="fullname", type="string"),
        *               @OA\Property(property="role_id", type="integer"),
        *               @OA\Property(property="email", type="string"),
        *               @OA\Property(property="status", type="integer"),
        *               @OA\Property(property="login_attempt", type="integer")
        *            ),
        *        ),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function put($id, Request $request)
    {
        $this->validate($request, [
            'password' => 'string|min:8',
            'fullname' => 'string',
            'role_id' => 'numeric',
            'email' => 'email:rfc,dns',
            'status' => 'numeric',
            'login_attempt' => 'numeric|max:1',
            // 'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $newData = $request->only(['password', 'fullname', 'role_id', 'email', 'status', 'login_attempt']);

        DB::beginTransaction();

        try {

            $data = User::find($id);
            if(is_null($data)){
                return $this->response('not_found');
            }

            if($request->hasfile('profile_image'))
            {
                Storage::delete('profile_images/' . $data->profile_image);

                $newFile = $request->file('profile_image');
                $imageName = Str::uuid() . '.' . $newFile->extension();

                // $img = Image::make($newFile->path())->encode('jpg');
                $img = Image::make($newFile->path());
                $img->resize(128, 128, function ($const) {
                    $const->aspectRatio();
                });

                $resource = $img->stream()->detach();
                Storage::put('profile_images/'.$imageName, $resource);

                $newData['profile_image'] = $imageName;
            }

            $newData['updated_at'] = now()->toDateTimeString(); // track when updated
            $newData['updated_by'] = Auth::user()->name;  // track who is updating this

            if($request->password !== null) {
                $newData['password'] = bcrypt($newData['password']);
            }

            $data->update($newData);

            unset($data['password']);  // remove password for responding updated user data
            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            //return error message
            return $this->response('not_valid', $e);
        }

        return $this->response('done', $data);
    }

    /** Delete User
        * @OA\Delete(
        * path="/api/users/{id}",
        * operationId="userDelete",
        * tags={"User"},
        * summary="Delete",
        *     @OA\Parameter(
        *       in="path",
        *       name="id",
        *       required=true,
        *       @OA\Schema(type="integer"),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function remove($id)
    {
        $data = User::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        try {
            $data['deleted_by'] = Auth::user()->name; // track who is deleting this
            $data->save(); // save before delete for tracking who is deleting
            $data->delete(); // soft delete
        }
        catch (\Exception $e) {
            return $this->response('not_valid', $e);
        }
        return $this->response('done', ["id"=>$id]);
    }

}
