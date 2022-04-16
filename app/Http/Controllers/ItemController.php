<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ItemController extends Controller
{

    /** Get Item List
        * @OA\Get(
        * path="/api/items",
        * operationId="itemList",
        * tags={"Item"},
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
        *               @OA\Property(property="category_id", type="integer"),
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
        $categoryId = $request->input('category_id');

        $skip = ($page-1) * $perPage;

        $records = Item::where(function ($query) use($type) {
            if($type !== null) {
                $query->where('type', $type);
            }
        })
        ->where(function ($query) use($categoryId) {
            if($categoryId !== null) {
                $query->where('category_id', $categoryId);
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

        $total = Item::where(function ($query) use($type) {
            if($type !== null) {
                $query->where('type', $type);
            }
        })
        ->where(function ($query) use($categoryId) {
            if($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('name', 'like', "%{$q}%");
            }
        })
        ->get()->count();

        $data['items'] = $records;
        $data['total'] = $total;

        if(!count($data)){
            return $this->response('no_data');
        }
        return $this->response('done', $data);
    }

    /** Get Item
        * @OA\Get(
        * path="/api/items/{id}",
        * operationId="itemGet",
        * tags={"Item"},
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
        $data = Item::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        return $this->response('done', $data);
    }

    /** Create Item
        * @OA\Post(
        * path="/api/items",
        * operationId="itemCreate",
        * tags={"Item"},
        * summary="Create",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"name", "category_id", "unit_id", "charge", "type"},
        *               @OA\Property(property="name", type="string"),
        *               @OA\Property(property="category_id", type="integer"),
        *               @OA\Property(property="unit_id", type="integer"),
        *               @OA\Property(property="charge", type="number"),
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
            'category_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'type' => 'required|string|max:255',
            'charge' => 'required|numeric',
        ]);

        try {
            $data = $request->only(['name', 'category_id', 'unit_id', 'type', 'charge']);
            $data['created_by'] = Auth::user()->name; // track who is creating this
            $result = Item::insertGetId($data);
            $data = array('id'=> $result) + $data; //add generated id infront of response data array
        } catch (\Exception $e) {
            //return error message
            return $this->response('not_valid', $e);
        }

        //return successful response
        return $this->response('created', $data);
    }

    /** Update Item
        * @OA\Put(
        * path="/api/items/{id}",
        * operationId="itemUpdate",
        * tags={"Item"},
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
        *               @OA\Property(property="category_id", type="integer"),
        *               @OA\Property(property="unit_id", type="integer"),
        *               @OA\Property(property="charge", type="number"),
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
            'category_id' => 'integer',
            'unit_id' => 'integer',
            'type' => 'string|max:255',
            'charge' => 'numeric',
        ]);

        $newData = $request->only(['name', 'category_id', 'unit_id', 'type', 'charge']);

        DB::beginTransaction();

        try {
            $data = Item::find($id);
            if(is_null($data)) {
                return $this->response('not_found');
            }

            $newData['updated_at'] = now()->toDateTimeString(); // track when updated
            $newData['updated_by'] = Auth::user()->name;  // track who is updating this

            $data->update($newData);
            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            //return error message
            return $this->response('not_valid', $e);
        }

        return $this->response('done', $data);
    }

    /** Delete Item
        * @OA\Delete(
        * path="/api/items/{id}",
        * operationId="itemDelete",
        * tags={"Item"},
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
        $data = Item::find($id);
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
