<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Disburse\Entities\Disburse;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\users_create;
use Modules\Report\Entities\DailyReportPayment;
use DB;
use DateTime;

class ApiReportDisburseController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summary(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_outlet'])) {
            $query1 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->where('disburse.disburse_status', 'Success');
            $query2 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->whereIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts']);
            $query3 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet'])
                ->where(function ($q){
                    $q->whereNull('disburse_status')
                        ->orWhereIn('disburse_status', ['Queued', 'Hold', 'Retry From Failed', 'Retry From Failed Payouts']);
                });
            $query4 = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')->where('transactions.id_outlet', $post['id_outlet']);

            if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
                $dateStart = date('Y-m-d', strtotime($post['date_start']));
                $dateEnd = date('Y-m-d', strtotime($post['date_end']));
                $query1 = $query1->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
                $query2 = $query2->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
                $query3 = $query3->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
                $query4 = $query4->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
            }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
                $currentDate = date('Y-m-d');
                $query1 = $query1->whereDate('transactions.transaction_date', $currentDate);
                $query2 = $query2->whereDate('transactions.transaction_date', $currentDate);
                $query3 = $query3->whereDate('transactions.transaction_date', $currentDate);
                $query4 = $query4->whereDate('transactions.transaction_date', $currentDate);
            }
            $success = $query1->sum('disburse_outlet_transactions.income_outlet');
            $fail = $query2->sum('disburse_outlet_transactions.income_outlet');
            $unprocessed = $query3->sum('disburse_outlet_transactions.income_outlet');
            $sum = $query4->selectRaw('SUM(disburse_outlet_transactions.fee_item) AS total_fee_item, SUM(disburse_outlet_transactions.payment_charge) AS total_mdr_charged,
                    SUM(disburse_outlet_transactions.income_outlet) AS total_income, SUM(transactions.transaction_grandtotal) AS total_grandtotal')->first();

            $result = [
                [
                    'title' => 'Disburse Success',
                    'amount' => number_format($success,2,",",".")
                ],
                [
                    'title' => 'Disburse Failed',
                    'amount' => number_format($fail,2,",",".")
                ],
                [
                    'title' => 'Disburse Unprocessed',
                    'amount' => number_format($unprocessed,2,",",".")
                ],
                [
                    'title' => 'Total Fee Item',
                    'amount' => number_format($sum['total_fee_item']??0,2,",",".")
                ],
                [
                    'title' => 'Total MDR PG',
                    'amount' => number_format($sum['total_mdr_charged']??0,2,",",".")
                ],
                [
                    'title' => 'Total Income',
                    'amount' => number_format($sum['total_income']??0,2,",",".")
                ],
                [
                    'title' => 'Total Grandtotal',
                    'amount' => number_format($sum['total_grandtotal']??0,2,",",".")
                ]
            ];

            return response()->json(MyHelper::checkGet($result));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function listTransaction(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_outlet'])){
            $data = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                ->join('users','users.id','=','transactions.id_user')
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at')
                ->where('transactions.id_outlet', $post['id_outlet'])
                ->select('transactions.transaction_grandtotal', 'transactions.transaction_receipt_number', 'transaction_pickups.order_id', 'transactions.id_transaction', 'transactions.transaction_date', 'users.name');

            if($post['status'] == 'unprocessed'){
                $data->where(function ($q){
                    $q->whereNull('disburse_status')
                        ->orWhereIn('disburse_status', ['Queued', 'Hold', 'Retry From Failed', 'Retry From Failed Payouts']);
                });
            }elseif($post['status'] == 'fail'){
                $data->whereIn('disburse.disburse_status', ['Fail', 'Failed Create Payouts']);
            }elseif ($post['status'] == 'success'){
                $data->where('disburse.disburse_status', 'Success');
            }

            if(isset($post['date_start']) && !empty($post['date_start']) &&
                isset($post['date_end']) && !empty($post['date_end'])){
                $start_date = date('Y-m-d', strtotime($post['date_start']));
                $end_date = date('Y-m-d', strtotime($post['date_end']));

                $data->whereDate('transactions.created_at', '>=', $start_date)
                    ->whereDate('transactions.created_at', '<=', $end_date);
            }

            if(isset($post['conditions']) && !empty($post['conditions'])){
                $rule = $post['rule']??'and';

                if($rule == 'and'){
                    foreach ($post['conditions'] as $condition){
                        if(!empty($condition['subject'])){
                            if($condition['operator'] == '='){
                                $data->where($condition['subject'], $condition['parameter']);
                            }else{
                                $data->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                }else{
                    $data->where(function ($q) use($post){
                        foreach ($post['conditions'] as $condition){
                            if(!empty($condition['subject'])){
                                if($condition['operator'] == '='){
                                    $q->orWhere($condition['subject'], $condition['parameter']);
                                }else{
                                    $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                }
                            }
                        }
                    });
                }
            }
            $order = $post['order']??'transaction_date';
            $orderType = $post['order_type']??'desc';

            $data = $data->orderBy($order, $orderType)->paginate(30);
            return response()->json(MyHelper::checkGet($data));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }
}
