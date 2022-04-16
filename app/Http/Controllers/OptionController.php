<?php

namespace App\Http\Controllers;

use App\Models\{User, Role, Category, Unit, Item, Patient};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
      case 'patient': $data['options'] = Patient::all(['id', 'name']); break;
    }
    return $this->response('done', $data);
  }

}
