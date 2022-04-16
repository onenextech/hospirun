<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class PatientController extends Controller
{

    /** Get Patient List
        * @OA\Get(
        * path="/api/patients",
        * operationId="patientList",
        * tags={"Patient"},
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

        $skip = ($page-1) * $perPage;

        $records = Patient::where(function ($query) use($q) {
            if($q !== "") {
                $query->where('name', 'like', "%{$q}%")
                ->orWhere('nrc_number', 'like', "%{$q}%")
                ->orWhere('cpi_number', 'like', "%{$q}%");
            }
        })
        ->orderBy($sortBy, $sortOrder)
        ->skip($skip)->take($perPage)
        ->get();

        $total = Patient::where(function ($query) use($q) {
            if($q !== "") {
                $query->where('name', 'like', "%{$q}%")
                ->orWhere('nrc_number', 'like', "%{$q}%")
                ->orWhere('cpi_number', 'like', "%{$q}%");
            }
        })
        ->get()->count();

        $data['patients'] = $records;
        $data['total'] = $total;

        if(!count($data)){
            return $this->response('no_data');
        }
        return $this->response('done', $data);
    }

    /** Get Patient
        * @OA\Get(
        * path="/api/patients/{id}",
        * operationId="patientGet",
        * tags={"Patient"},
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
        $data = Patient::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        return $this->response('done', $data);
    }

    /** Create Patient
        * @OA\Post(
        * path="/api/patients",
        * operationId="patientCreate",
        * tags={"Patient"},
        * summary="Create",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"name", "type"},
        *               @OA\Property(property="name", type="string"),
        *               @OA\Property(property="gender", type="string"),
        *               @OA\Property(property="date_of_birth", type="string"),
        *               @OA\Property(property="age", type="integer"),
        *               @OA\Property(property="address", type="string"),
        *               @OA\Property(property="phone", type="string"),
        *               @OA\Property(property="blood_group", type="string"),
        *               @OA\Property(property="nrc_number", type="string"),
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
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|date_format:Y-m-d',
            'age' => 'required|integer',
            'nrc_number' => 'required|string',
        ]);

        try {
            $data = $request->only(['name', 'gender', 'date_of_birth', 'age', 'address', 'phone', 'blood_group', 'nrc_number']);
            $data['created_by'] = Auth::user()->name; // track who is creating this
            $result = Patient::insertGetId($data);
            $data = array('id'=> $result) + $data; //add generated id infront of response data array
        } catch (\Exception $e) {
            //return error message
            return $this->response('not_valid', $e);
        }

        //return successful response
        return $this->response('created', $data);
    }

    /** Update Patient
        * @OA\Put(
        * path="/api/patients/{id}",
        * operationId="patientUpdate",
        * tags={"Patient"},
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
        *               @OA\Property(property="gender", type="string"),
        *               @OA\Property(property="date_of_birth", type="string"),
        *               @OA\Property(property="age", type="integer"),
        *               @OA\Property(property="address", type="string"),
        *               @OA\Property(property="phone", type="string"),
        *               @OA\Property(property="blood_group", type="string"),
        *               @OA\Property(property="nrc_number", type="string"),
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
            'gender' => 'in:Male,Female',
            'date_of_birth' => 'date|date_format:Y-m-d',
            'age' => 'integer',
            'nrc_number' => 'string',
        ]);

        $newData = $request->only(['name', 'gender', 'date_of_birth', 'age', 'address', 'phone', 'blood_group', 'nrc_number']);

        DB::beginTransaction();

        try {

            $data = Patient::find($id);
            if(is_null($data)) {
                return $this->response('not_found');
            }

            $newData['updated_at'] = now()->toDateTimeString(); // track when updated
            $newData['updated_by'] = Auth::user()->name;  // track who is updating this

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

    /** Delete Patient
        * @OA\Delete(
        * path="/api/patients/{id}",
        * operationId="patientDelete",
        * tags={"Patient"},
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
        $data = Patient::find($id);
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
