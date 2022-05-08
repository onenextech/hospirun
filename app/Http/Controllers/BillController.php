<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{

    /** Get Bill List
        * @OA\Get(
        * path="/api/bills",
        * operationId="billList",
        * tags={"Bill"},
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
        *               @OA\Property(property="bill_dates", type="object"),
        *               @OA\Property(property="patient_id", type="integer"),
        *               @OA\Property(property="status", type="string"),
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
        $status = $request->input('status');
        $patientId = $request->input('patient_id');
        $billDates = $request->input('bill_dates');

        $skip = ($page-1) * $perPage;

        $records = Bill::with(['bill_items', 'payment'])
        ->where(function ($query) use($status) {
            if($status !== null) {
                $query->where('status', $status);
            }
        })
        ->where(function ($query) use($patientId) {
            if($patientId !== null) {
                $query->where('patient_id', $patientId);
            }
        })
        ->where(function ($query) use($billDates) {
            if($billDates !== null) {
                $from = $billDates['from'];
                $to = $billDates['to'];
                $query->whereBetween('date', [$from, $to]);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('remark', 'like', "%{$q}%")
                ->orWhere('patient_address', 'like', "%{$q}%");
            }
        })
        ->orderBy($sortBy, $sortOrder)
        ->skip($skip)->take($perPage)
        ->get();

        $total = Bill::where(function ($query) use($status) {
            if($status !== null) {
                $query->where('status', $status);
            }
        })
        ->where(function ($query) use($patientId) {
            if($patientId !== null) {
                $query->where('patient_id', $patientId);
            }
        })
        ->where(function ($query) use($billDates) {
            if($billDates !== null) {
                $from = $billDates['from'];
                $to = $billDates['to'];
                $query->whereBetween('date', [$from, $to]);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('remark', 'like', "%{$q}%")
                ->orWhere('patient_address', 'like', "%{$q}%");
            }
        })
        ->get()->count();

        $data['bills'] = $records;
        $data['total'] = $total;

        if(!count($data)){
            return $this->response('no_data');
        }
        return $this->response('done', $data);
    }

    /** Get Bill
        * @OA\Get(
        * path="/api/bills/{id}",
        * operationId="billGet",
        * tags={"Bill"},
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
        $data = Bill::with(['bill_items', 'payment'])->find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        return $this->response('done', $data);
    }

    /** Create Bill
        * @OA\Post(
        * path="/api/bills",
        * operationId="billCreate",
        * tags={"Bill"},
        * summary="Create",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"date", "patient_id", "patient_name", "patient_phone", "total_amount", "status", "bill_items"},
        *               @OA\Property(property="date", type="string"),
        *               @OA\Property(property="patient_id", type="integer"),
        *               @OA\Property(property="patient_name", type="string"),
        *               @OA\Property(property="patient_phone", type="string"),
        *               @OA\Property(property="patient_address", type="string"),
        *               @OA\Property(property="total_amount", type="number"),
        *               @OA\Property(property="remark", type="string"),
        *               @OA\Property(property="status", type="string"),
        *               @OA\Property(property="bill_items", type="string"),
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
            'date' => 'required|string',
            'patient_id' => 'required|integer',
            'patient_name' => 'required|string|max:255',
            'patient_phone' => 'required|string|max:255',
            'total_amount' => 'required|numeric',
            'status' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['date', 'patient_id', 'patient_name', 'patient_phone', 'patient_address', 'total_amount', 'remark', 'status']);

            $data['created_by'] = Auth::user()->id; // track who is creating this
            $bill_id = Bill::insertGetId($data);

            $data_items = $request->input('bill_items');
            $bill_items = [];

            foreach ($data_items as $item) {
                $item['bill_id'] = $bill_id;
                $item['created_at'] = now()->toDateTimeString();
                $item['created_by'] = Auth::user()->id;
                $bill_items[] = $item;
            }

            BillItem::insert($bill_items);

            // if status is 'Completed', it means bill is paid. So, insert record to payment table
            if($data['status'] == 'Completed') {
                $payment = $request->only(['date', 'patient_id', 'patient_name', 'patient_phone', 'patient_address', 'remark']);
                $payment['amount'] = $request->only('total_amount');
                $payment['subject'] = 'Bill';
                $payment['bill_id'] = $bill_id;
                Payment::insert($payment);
            }

            $data = array('id'=> $bill_id) + $data; //add generated id infront of response data array
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->response('not_valid', $e);
        }

        //return successful response
        return $this->response('created', $data);
    }

    /** Update Bill
        * @OA\Put(
        * path="/api/bills/{id}",
        * operationId="billUpdate",
        * tags={"Bill"},
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
        *               @OA\Property(property="date", type="string"),
        *               @OA\Property(property="patient_id", type="integer"),
        *               @OA\Property(property="patient_name", type="string"),
        *               @OA\Property(property="patient_phone", type="string"),
        *               @OA\Property(property="patient_address", type="string"),
        *               @OA\Property(property="total_amount", type="number"),
        *               @OA\Property(property="remark", type="string"),
        *               @OA\Property(property="status", type="string"),
        *               @OA\Property(property="bill_items", type="string"),
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
            'date' => 'nullable|string',
            'patient_id' => 'nullable|integer',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:255',
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string|max:255',
        ]);

        $newData = $request->only(['date', 'patient_id', 'patient_name', 'patient_phone', 'patient_address', 'total_amount', 'remark', 'status']);

        DB::beginTransaction();

        try {
            $data = Bill::find($id);
            if(is_null($data)) {
                return $this->response('not_found');
            }

            $newData['updated_at'] = now()->toDateTimeString(); // track when updated
            $newData['updated_by'] = Auth::user()->id;  // track who is updating this
            $data->update($newData);

            if($request->has(['bill_items'])) {
                // delete all existing bill items
                BillItem::where('bill_id', $id)->delete();

                $data_items = $request->input('bill_items');
                $bill_items = [];
                foreach ($data_items as $item) {
                    $item['bill_id'] = $id;
                    $item['created_at'] = now()->toDateTimeString();
                    $item['created_by'] = Auth::user()->id;
                    $bill_items[] = $item;
                }

                BillItem::insert($bill_items);
            }

            // if status is 'Completed', it means bill is paid. So, insert record to payment table
            if($data['status'] == 'Completed') {
                // delete first existing payment
                Payment::where('bill_id', $id)->delete();

                $payment = $request->only(['date', 'patient_id', 'patient_name', 'patient_phone', 'patient_address', 'remark']);
                $payment['amount'] = $request->only('total_amount');
                $payment['subject'] = 'Bill';
                $payment['bill_id'] = $id;
                Payment::insert($payment);
            }

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            //return error message
            return $this->response('not_valid', $e);
        }

        return $this->response('done', $data);
    }

    /** Delete Bill
        * @OA\Delete(
        * path="/api/bills/{id}",
        * operationId="billDelete",
        * tags={"Bill"},
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
        $data = Bill::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }

        DB::beginTransaction();
        try {
            $data['deleted_by'] = Auth::user()->id; // track who is deleting this
            $data->save(); // save before delete for tracking who is deleting
            $data->delete(); // soft delete

            BillItem::where('bill_id', $id)->delete();
            Payment::where('bill_id', $id)->delete();

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            return $this->response('not_valid', $e);
        }
        return $this->response('done', ["id"=>$id]);
    }

}
