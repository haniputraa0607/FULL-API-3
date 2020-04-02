<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\Disburse;

class ApiDisburseController extends Controller
{
    public function getOutlets(Request $request){
        $post = $request->json()->all();

        $outlet = Outlet::leftJoin('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name', 'outlets.id_bank_name', 'bank_name.bank_name', 'bank_name.bank_code', 'account_number', 'recipient_name');

        if(isset($post['id_user_franchisee']) && !empty($post['id_user_franchisee'])){
            $outlet->join('user_franchisee_outlet', 'outlets.id_outlet', 'user_franchisee_outlet.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchisee', $post['id_user_franchisee']);
        }

        if(isset($post['page'])){
            $outlet = $outlet->paginate(25);
        }else{
            $outlet = $outlet->get()->toArray();
        }

        return response()->json(MyHelper::checkGet($outlet));
    }

    function listDisburse(Request $request, $status){
        $post = $request->json()->all();

        $data = Disburse::select('id_disburse', 'disburse_nominal', 'disburse_status', 'beneficiary_bank_name', 'beneficiary_account_number',
                'recipient_name', 'created_at', 'updated_at')->orderBy('created_at','desc');

        if($status != 'all'){
            $data->where('disburse_status', $status);
        }

        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    function listTrx(Request $request){
        $post = $request->json()->all();

        $data = Transaction::leftJoin('disburse', 'transactions.id_disburse', 'disburse.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->where('transactions.trasaction_type', '!=', 'Offline')
                ->select('disburse_status', 'transactions.*')->paginate(25);

        return response()->json(MyHelper::checkGet($data));
    }

    function detailDisburse(Request $request ,$id){
        $post = $request->json()->all();

        $disburse = Disburse::where('disburse.id_disburse', $id)->first();
        $data = Transaction::leftJoin('disburse', 'transactions.id_disburse', 'disburse.id_disburse')
            ->where('transactions.transaction_payment_status', 'Completed')
            ->where('transactions.trasaction_type', '!=', 'Offline')
            ->where('transactions.trasaction_type', '!=', 'Offline')
            ->where('transactions.id_disburse', $id)
            ->select('disburse.disburse_status', 'transactions.*')->paginate(25);

        $result = [
            'status' => 'success',
            'result' => [
                'data_disburse' => $disburse,
                'list_trx' => $data
            ]
        ];
        return response()->json($result);
    }
}
