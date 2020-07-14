<?php

namespace Modules\PaymentMethod\Http\Controllers;

use App\Lib\MyHelper;
use App\Http\Models\PaymentMethod;
use App\Http\Models\PaymentMethodCategory;
use App\Http\Models\PaymentMethodOutlet;
use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $payment_method = PaymentMethod::with('payment_method_category')->get();
        return response()->json(MyHelper::checkGet($payment_method));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post  = $request->json()->all();
        $save = PaymentMethod::create($post);

        return MyHelper::checkCreate($save);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $data = PaymentMethod::find($id);

        return response()->json(MyHelper::checkGet($data));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $post  = $request->json()->all();

        if(isset($post['status']) && $post['status'] == 'on'){
            $post['status'] = 'Enable';
        }else{
            $post['status'] = 'Disable';
        }

        if(isset($post['is_global']) && $post['is_global'] == 'on'){
            //delete all payment method outlet for this payment method id
            $delete = PaymentMethodOutlet::where('id_payment_method', $id)->delete();
            unset($post['is_global']);
        }

        $save = PaymentMethod::find($id)->update($post);
        return MyHelper::checkUpdate($save);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $payment_method = PaymentMethod::find($id);

        if($payment_method){
            return MyHelper::checkDelete($payment_method->delete());
        }

        return response()->json(['status' => 'fail']);
    }

    public function getDifferentPaymentMethod(Request $request, $id) {

        $outlets = Outlet::select('id_outlet','outlet_code','outlet_name');

        if($keyword = $request->json('keyword')){
            $outlets->where('outlet_code','like',"%$keyword%")
                 ->orWhere('outlet_name','like',"%$keyword%");
        }

        $outlets = $outlets->get()->toArray();
        
        $payment_method = PaymentMethod::find($id);

        //assign default payment method status to all outlet
        foreach($outlets as $key => $outlet){
            $outlets[$key]['status'] = $payment_method->status ?? $payment_method['status'];
        }

        $outlet_payments = PaymentMethodOutlet::with([
            'outlet' => function($query) use($request){
                $query->select('id_outlet','outlet_code','outlet_name');

                if($keyword = $request->json('keyword')){
                    $query->where('outlet_code','like',"%$keyword%")
                         ->orWhere('outlet_name','like',"%$keyword%");
                }
            }
        ])->where('id_payment_method', $id)->get();

        if($outlet_payments){
            //modified status of outlet
            foreach($outlets as $key => $outlet){
                foreach($outlet_payments as $outlet_payment){
                    if($outlets[$key]['id_outlet'] == $outlet_payment->id_outlet && $id = $outlet_payment->id_payment_method){
                        $outlets[$key]['status'] = $outlet_payment->status;
                    }
                }
            }
        }

        return response()->json($outlets);

    }

    public function updateDifferentPaymentMethod(Request $request) {
        $post = $request->json()->all();
        $update = PaymentMethodOutlet::updateOrCreate(['id_outlet' => $post['id_outlet'][0]??'', 'id_payment_method' => $post['id_payment_method']],['status'=>$post['status']]);
        if($update){
            return [
                'status'=>'success',
                'result'=>$post['status']
            ];
        }
        return ['status'=>'fail'];
    }

    public function getItemWithID($id){
        $data = PaymentMethod::find($id);
        return response()->json(MyHelper::checkGet($data));exit;
    }
}
