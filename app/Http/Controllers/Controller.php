<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *    title="Doh Eain - Portal",
 *    version="1.0.0",
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $statusCodes = [
        'done' => [ 'status'=> 200 , 'message'=> 'Done'],
        'created' => [ 'status'=> 201 , 'message' => 'Created'],
        'no_data' => [ 'status'=> 204 , 'message' => 'No Content'],
        'not_valid' => [ 'status'=> 400 , 'message' => 'Not Valid'],
        'not_found' => [ 'status'=> 404 , 'message' => 'Not Found'],
        'conflict' => [ 'status'=> 409 , 'message' => 'Conflit'],
        'logout' => [ 'status'=> 401 , 'message' => 'Unauthorized'],
        'locked' => [ 'status'=> 401 , 'message' => 'Locked'],
        'inactive' => [ 'status'=> 401 , 'message' => 'Inactive'],
        'permission' => [ 'status'=> 403 , 'message' => 'Forbidden'],
        'error' => ['status' => 500, 'message' => 'Internal Server Error']
    ];

    protected function response($status, $data = [])
    {
        return response()->json(['message'=>$this->statusCodes[$status]['message'], 'data'=>$data], $this->statusCodes[$status]['status']);
    }

}
