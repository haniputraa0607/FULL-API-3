<?php

namespace Modules\Disburse\Http\Controllers;

use App\Http\Models\DailyReportTrx;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankName;
use Modules\Disburse\Entities\Disburse;

use DB;
use Modules\Disburse\Entities\UserFranchise;

class ApiDisburseController extends Controller
{
    public function dashboard(Request $request){
        $post = $request->json()->all();
        $nominal_success = Disburse::where('disburse.disburse_status', 'Success');
        $nominal_fail = Disburse::where('disburse.disburse_status', 'Fail');
        $nominal_trx = DailyReportTrx::where('trx_type', 'Online');

        if(isset($post['id_outlet']) && !empty($post['id_outlet']) && $post['id_outlet'] != 'all'){
            $nominal_success->where('disburse.id_outlet', $post['id_outlet']);
            $nominal_fail->where('disburse.id_outlet', $post['id_outlet']);
            $nominal_trx->where('daily_report_trx.id_outlet', $post['id_outlet']);
        }

        if(isset($post['fitler_date']) && $post['fitler_date'] == 'today'){

            $nominal_success->whereDate('disburse.created_at', date('Y-m-d'));
            $nominal_fail->whereDate('disburse.created_at', date('Y-m-d'));
            $nominal_trx->whereDate('daily_report_trx.trx_date', date('Y-m-d'));

        }elseif(isset($post['fitler_date']) && $post['fitler_date'] == 'specific_date'){
            if(isset($post['start_date']) && !empty($post['start_date']) &&
                isset($post['end_date']) && !empty($post['end_date'])){
                $start_date = date('Y-m-d', strtotime($post['start_date']));
                $end_date = date('Y-m-d', strtotime($post['end_date']));

                $nominal_success->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                $nominal_fail->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
                $nominal_trx->whereDate('daily_report_trx.trx_date', '>=', $start_date)
                    ->whereDate('daily_report_trx.trx_date', '<=', $end_date);
            }
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $nominal_success->join('user_franchisee_outlet', 'user_franchisee_outlet.id_outlet', 'disburse.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchise', $post['id_user_franchise']);
            $nominal_fail->join('user_franchisee_outlet', 'user_franchisee_outlet.id_outlet', 'disburse.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchise', $post['id_user_franchise']);
            $nominal_trx->join('user_franchisee_outlet', 'user_franchisee_outlet.id_outlet', 'daily_report_trx.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        $nominal_success = $nominal_success->sum('disburse.disburse_nominal');
        $nominal_fail = $nominal_fail->sum('disburse.disburse_nominal');
        $nominal_trx = $nominal_trx->sum('trx_grand');
        $result = [
            'status' => 'success',
            'result' => [
                'nominal_success' => $nominal_success,
                'nominal_fail' => $nominal_fail,
                'nominal_trx' => $nominal_trx
            ]
        ];
        return response()->json($result);
    }

    public function getOutlets(Request $request){
        $post = $request->json()->all();

        $outlet = Outlet::leftJoin('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name', 'outlets.id_bank_name',
                    'outlets.beneficiary_name', 'outlets.beneficiary_alias', 'outlets.beneficiary_account', 'outlets.beneficiary_email',
                    'bank_name.bank_name', 'bank_name.bank_code');

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $outlet->join('user_franchisee_outlet', 'outlets.id_outlet', 'user_franchisee_outlet.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_bank'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.bank_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.bank_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_name'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.beneficiary_name', $row['parameter']);
                            }else{
                                $outlet->where('outlets.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_alias'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.beneficiary_alias', $row['parameter']);
                            }else{
                                $outlet->where('outlets.beneficiary_alias', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_account'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.beneficiary_account', $row['parameter']);
                            }else{
                                $outlet->where('outlets.beneficiary_account', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'beneficiary_email'){
                            if($row['operator'] == '='){
                                $outlet->where('outlets.beneficiary_email', $row['parameter']);
                            }else{
                                $outlet->where('outlets.beneficiary_email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $outlet->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_bank'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.bank_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.bank_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.beneficiary_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_alias'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.beneficiary_alias', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.beneficiary_alias', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_account'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.beneficiary_account', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.beneficiary_account', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'beneficiary_email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.beneficiary_email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.beneficiary_email', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
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

        $data = Disburse::join('outlets', 'outlets.id_outlet', 'disburse.id_outlet')
            ->join('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
                ->select('outlets.outlet_name', 'outlets.outlet_code', 'disburse.id_disburse', 'disburse.disburse_nominal', 'disburse.disburse_status', 'disburse.beneficiary_account_number',
                'disburse.beneficiary_name', 'disburse.created_at', 'disburse.updated_at', 'bank_name.bank_code', 'bank_name.bank_name')->orderBy('disburse.created_at','desc');

        if($status != 'all'){
            $data->where('disburse.disburse_status', ucfirst($status));
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data->join('user_franchisee_outlet', 'user_franchisee_outlet.id_outlet', 'disburse.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('disburse.created_at', '>=', $start_date)
                ->whereDate('disburse.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'bank_name'){
                            $data->where('bank_name.id_bank_name', $row['operator']);
                        }

                        if($row['subject'] == 'status'){
                            $data->where('disburse.disburse_status', $row['operator']);
                        }

                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $data->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $data->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $data->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $data->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'account_number'){
                            if($row['operator'] == '='){
                                $data->where('disburse.beneficiary_account_number', $row['parameter']);
                            }else{
                                $data->where('disburse.beneficiary_account_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'recipient_name'){
                            if($row['operator'] == '='){
                                $data->where('disburse.beneficiary_name', $row['parameter']);
                            }else{
                                $data->where('disburse.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'bank_name'){
                                $subquery->orWhere('bank_name.id_bank_name', $row['operator']);
                            }

                            if($row['subject'] == 'status'){
                                $subquery->orWhere('disburse.disburse_status', $row['operator']);
                            }

                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'account_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('disburse.beneficiary_account_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('disburse.beneficiary_account_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'recipient_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWheree('disburse.beneficiary_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('disburse.beneficiary_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    function listTrx(Request $request){
        $post = $request->json()->all();

        $data = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                ->leftJoin('disburse', 'transactions.id_disburse', 'disburse.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->where('transactions.trasaction_type', '!=', 'Offline')
                ->select('disburse_status', 'transactions.*', 'outlets.outlet_name', 'outlets.outlet_code');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('transactions.created_at', '>=', $start_date)
                ->whereDate('transactions.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            if($row['operator'] == 'Pending'){
                                $data->whereNull('disburse.disburse_status');
                            }else{
                                $data->where('disburse.disburse_status', $row['operator']);
                            }

                        }

                        if($row['subject'] == 'recipient_number'){
                            if($row['operator'] == '='){
                                $data->where('transactions.transaction_receipt_number', $row['parameter']);
                            }else{
                                $data->where('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'status'){
                                if($row['operator'] == 'Pending'){
                                    $subquery->orWhereNull('disburse_status');
                                }else{
                                    $subquery->orWhere('disburse_status', $row['operator']);
                                }

                            }

                            if($row['subject'] == 'recipient_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transactions.transaction_receipt_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    function detailDisburse(Request $request ,$id){
        $post = $request->json()->all();

        $disburse = Disburse::join('outlets', 'outlets.id_outlet', 'disburse.id_outlet')
            ->join('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
            ->where('disburse.id_disburse', $id)
            ->select('outlets.outlet_name', 'outlets.outlet_code', 'disburse.id_disburse', 'disburse.disburse_nominal', 'disburse.disburse_status', 'disburse.beneficiary_account_number',
                'disburse.beneficiary_name', 'disburse.created_at', 'disburse.updated_at', 'bank_name.bank_code', 'bank_name.bank_name')->first();
        $data = Transaction::join('disburse_transactions', 'disburse_transactions.id_transaction', 'transactions.id_transaction')
            ->where('disburse_transactions.id_disburse', $id)
            ->select('disburse_transactions.*', 'transactions.*')->paginate(25);

        $result = [
            'status' => 'success',
            'result' => [
                'data_disburse' => $disburse,
                'list_trx' => $data
            ]
        ];
        return response()->json($result);
    }

    function listDisburseDataTable(Request $request, $status){
        $post = $request->json()->all();

        $start = $post['start'];
        $length = $post['length'];

        $data = Disburse::join('outlets', 'outlets.id_outlet', 'disburse.id_outlet')
            ->join('bank_name', 'bank_name.id_bank_name', 'outlets.id_bank_name')
            ->select('disburse.id_disburse as 0', DB::raw("CONCAT (outlets.outlet_code, ' - ',outlets.outlet_name) as '1'"), DB::raw("DATE_FORMAT(disburse.created_at, '%d %b %Y %H:%i') as '2'"), DB::raw('FORMAT(disburse.disburse_nominal,0) as "3"'), 'disburse.disburse_status',
                'bank_name.bank_name as 4', 'disburse.beneficiary_account_number as 5', 'disburse.beneficiary_name as 6', 'disburse.updated_at', 'bank_name.bank_code')->orderBy('disburse.created_at','desc');

        if($status != 'all'){
            $data->where('disburse.disburse_status', $status);
        }

        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data->join('user_franchisee_outlet', 'user_franchisee_outlet.id_outlet', 'disburse.id_outlet')
                ->where('user_franchisee_outlet.id_user_franchise', $post['id_user_franchise']);
        }

        if(isset($post['fitler_date']) &&  $post['fitler_date']== 'today'){
            $data->whereDate('disburse.created_at', date('Y-m-d'));
        }elseif(isset($post['fitler_date']) &&  $post['fitler_date'] == 'specific_date'){
            if(isset($post['start_date']) && !empty($post['start_date']) &&
                isset($post['end_date']) && !empty($post['end_date'])){
                $start_date = date('Y-m-d', strtotime($post['start_date']));
                $end_date = date('Y-m-d', strtotime($post['end_date']));

                $data->whereDate('disburse.created_at', '>=', $start_date)
                    ->whereDate('disburse.created_at', '<=', $end_date);
            }
        }

        if(isset($post['id_outlet']) && !empty($post['id_outlet']) && $post['id_outlet'] != 'all'){
            $data->where('disburse.id_outlet', $post['id_outlet']);
        }

        $total = $data->count();
        $data = $data->skip($start)->take($length)->get()->toArray();
        $result = [
            'status' => 'success',
            'result' => $data,
            'total' => $total
        ];

        return response()->json($result);
    }

    function syncListBank(){
        $getListBank = MyHelper::connectIris('GET','api/v1/beneficiary_banks',[]);

        if(isset($getListBank['status']) && $getListBank['status'] == 'success'){
            $getCurrentListBank = BankName::get()->toArray();
            $currentBank = array_column($getCurrentListBank, 'bank_code');

            $arrTmp = [];
            foreach ($getListBank['response']['beneficiary_banks'] as $dt){
                $checkExist = array_search($dt['code'], $currentBank);
                if($checkExist === false){
                    $arrTmp[] = [
                        'bank_code' => $dt['code'],
                        'bank_name' => $dt['name']
                    ];
                }
            }

            BankName::insert($arrTmp);
        }

        return 'success';
    }


    function userFranchise(Request $request){
        $post = $request->json()->all();

        if(isset($post['user_type'])){
            $data = UserFranchise::where('user_franchise_type', $post['user_type'])->get()->toArray();
        }elseif(isset($post['phone'])){
            $data = UserFranchise::where('phone', $post['phone'])->first();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function userFranchiseResetPassword(Request $request){
        $post = $request->json()->all();
        $get = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->first();

        if(!password_verify($post['current_pin'], $get['password'])){
            return response()->json(['status' => 'fail', 'message' => 'Current pin does not match']);
        }else{
            $update = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->update([
                'password' => bcrypt($post['pin'])
            ]);

            if($update){
                return response()->json(['status' => 'success']);
            }else{
                return response()->json(['status' => 'fail', 'message' => 'Failed update pin']);
            }
        }
    }

}
