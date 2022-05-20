<?php

namespace App\Http\Controllers;

use App\Models\{User, Role, Category, Unit, Item, Patient};
use Illuminate\Http\Request;

class OptionController extends Controller
{

  /** Get Option Data (id, name) of an object
    * @OA\Get(
    * path="/api/options/{object}",
    * operationId="optionGet",
    * tags={"Common"},
    * summary="Get",
    * description="Get id and name data array in database table. Parameter {object} is the table name [user, role, unit, category, item, patient]",
    *     @OA\Parameter(
    *       in="path",
    *       name="object",
    *       required=true,
    *       @OA\Schema(type="string"),
    *     ),
    *     @OA\Response(
    *          response=200,
    *          description="Success"
    *     ),
    * )
  */
  public function get($object)
  {
    $data = [];
    switch($object) {
        case 'user': $data['options'] = User::all(['id', 'name']); break;
        case 'role': $data['options'] = Role::all(['id', 'name']); break;
        case 'unit': $data['options'] = Unit::all(['id', 'name']); break;
        case 'category': $data['options'] = Category::all(['id', 'name']); break;
        case 'item': $data['options'] = Item::all(['id', 'name']); break;
        case 'type': $data['options'] = config('magixsupport.category.type'); break;
        case 'patient': $data['options'] = Patient::all(['id', 'name']); break;
        case 'gender': $data['genders'] = config('magixsupport.gender'); break;
        case 'patient_status': $data['statuses'] = config('magixsupport.patient.status'); break;
    }
    return $this->response('done', $data);
  }

  /** Get Multiple Option Data (id, name) of objects
    * @OA\Get(
    * path="/api/options",
    * operationId="multipleOptionGet",
    * tags={"Common"},
    * summary="Get Multiple Options",
    * description="Get id and name data array in database table or predefined object. Property objects comma seperated value of object names [user, role, unit, category, item, patient, type]",
    *     @OA\RequestBody(
    *         @OA\MediaType(
    *            mediaType="application/json",
    *            @OA\Schema(
    *               required={"objects"},
    *               @OA\Property(property="objects", type="string"),
    *            ),
    *        ),
    *     ),
    *     @OA\Response(
    *          response=200,
    *          description="Success"
    *     ),
    * )
  */
  public function getMultiple(Request $request)
  {
    // validate incoming request
    $this->validate($request, [
        'objects' => 'required|string',
    ]);

    $data = [];
    $objects = explode(",", $request->input('objects'));
    foreach($objects as $object) {
        switch($object) {
            case 'user': $data['users'] = User::all(['id', 'name']); break;
            case 'role': $data['roles'] = Role::all(['id', 'name']); break;
            case 'unit': $data['units'] = Unit::all(['id', 'name']); break;
            case 'category': $data['categories'] = Category::all(['id', 'name']); break;
            case 'item': $data['items'] = Item::all(['id', 'name']); break;
            case 'type': $data['types'] = config('magixsupport.category.type'); break;
            case 'patient': $data['patients'] = Patient::all(['id', 'name']); break;
            case 'gender': $data['genders'] = config('magixsupport.gender'); break;
            case 'patient_status': $data['statuses'] = config('magixsupport.patient.status'); break;
        }
    }
    return $this->response('done', $data);
  }

}
