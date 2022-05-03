<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{

    /** Get Category List
        * @OA\Get(
        * path="/api/categories",
        * operationId="categoryList",
        * tags={"Category"},
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
        *               @OA\Property(property="type", type="string"),
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
        $records = null;

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
        $type = $request->input('type');

        $skip = ($page-1) * $perPage;

        $records = Category::where(function ($query) use($type) {
            if($type !== null) {
                $query->where('type', $type);
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

        $total = Category::where(function ($query) use($type) {
            if($type !== null) {
                $query->where('type', $type);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('name', 'like', "%{$q}%");
            }
        })
        ->get()->count();

        $data['categories'] = $records;
        $data['total'] = $total;

        if(!count($data)){
            return $this->response('no_data');
        }
        return $this->response('done', $data);
    }

    /** Get Category
        * @OA\Get(
        * path="/api/categories/{id}",
        * operationId="categoryGet",
        * tags={"Category"},
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
        $data = Category::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        return $this->response('done', $data);
    }

    /** Create Category
        * @OA\Post(
        * path="/api/categories",
        * operationId="categoryCreate",
        * tags={"Category"},
        * summary="Create",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"name", "type"},
        *               @OA\Property(property="name", type="string"),
        *               @OA\Property(property="type", type="string"),
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
        //validate incoming request
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        try {
            $data = $request->only(['name', 'type', 'description']);
            $data['created_by'] = Auth::user()->id; // track who is creating this
            $result = Category::insertGetId($data);
            $data = array('id'=> $result) + $data; //add generated id infront of response data array
        } catch (\Exception $e) {
            //return error message
            return $this->response('not_valid', $e);
        }

        //return successful response
        return $this->response('created', $data);
    }

    /** Update Category
        * @OA\Put(
        * path="/api/categories/{id}",
        * operationId="categoryUpdate",
        * tags={"Category"},
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
        *               @OA\Property(property="name", type="string"),
        *               @OA\Property(property="type", type="string"),
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
            'name' => 'string|max:255',
            'type' => 'string|max:255',
        ]);

        $newData = $request->only(['name', 'type', 'description']);

        DB::beginTransaction();

        try {

            $data = Category::find($id);
            if(is_null($data)) {
                return $this->response('not_found');
            }

            $newData['updated_at'] = now()->toDateTimeString(); // track when updated
            $newData['updated_by'] = Auth::user()->id;  // track who is updating this

            $data->update($newData);
            DB::commit();
        }
        catch(\Exception $e) {
            DB::rollBack();
            //return error message
            return $this->response('not_valid', $e);
        }

        return $this->response('done', $data);
    }

    /** Delete Category
        * @OA\Delete(
        * path="/api/categories/{id}",
        * operationId="categoryDelete",
        * tags={"Category"},
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
        $data = Category::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        try {
            $data['deleted_by'] = Auth::user()->id; // track who is deleting this
            $data->save(); // save before delete for tracking who is deleting
            $data->delete(); // soft delete
        }
        catch (\Exception $e) {
            return $this->response('not_valid', $e);
        }
        return $this->response('done', ["id"=>$id]);
    }

}
