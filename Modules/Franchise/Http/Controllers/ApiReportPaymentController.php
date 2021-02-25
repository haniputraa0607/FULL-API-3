<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\users_create;
use Modules\Report\Entities\DailyReportPayment;
use DB;
use DateTime;

class ApiReportPaymentController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summaryPaymentMethod(Request $request){
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultet::where('id_user_franchise' , auth()->user()->id_user_franchise)->first()['id_outlet']??NULL;

        if($id_oultet){
            $listPayment = DailyReportPayment::where('id_outlet', $id_oultet)
                ->where('refund_with_point', 0)
                ->groupBy('payment_type')->groupBy('trx_payment')
                ->select('payment_type', 'trx_payment')
                ->get()->toArray();

            if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
                $dateStart = date('Y-m-d', strtotime($post['date_start']));
                $dateEnd = date('Y-m-d', strtotime($post['date_end']));
                $payments = DailyReportPayment::where('trx_date', '>=', $dateStart)
                    ->where('trx_date', '<=', $dateEnd)
                    ->where('id_outlet', $id_oultet)
                    ->where('refund_with_point', 0)
                    ->groupBy('payment_type')->groupBy('trx_payment')
                    ->select(DB::raw('CONCAT(CASE WHEN payment_type IS NULL THEN trx_payment
                        ELSE payment_type END, "_", trx_payment) as "key"'), DB::raw('SUM(trx_payment_nominal) as total_amount'),
                        DB::raw('(CASE WHEN payment_type IS NULL THEN trx_payment
                        ELSE payment_type END) as payment_type'), 'trx_payment')->get()->toArray();
            }elseif((isset($post['filter_type']) && $post['filter_type'] == 'today') || empty($post)){
                $currentDate = date('Y-m-d');
                $getData = Transaction::join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
                    ->select('transactions.transaction_grandtotal', 'transactions.transaction_receipt_number', 'transaction_pickups.order_id', 'transactions.id_transaction', 'transactions.transaction_date',
                        'payment_type', 'payment_method', 'transaction_payment_midtrans.gross_amount', 'transaction_payment_ipay88s.amount',
                        'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay', 'transaction_payment_shopee_pays.amount as shopee_amount',
                        'transaction_payment_subscriptions.subscription_nominal', 'balance_nominal')
                    ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                    ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
                    ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
                    ->leftJoin('transaction_payment_subscriptions', 'transactions.id_transaction', '=', 'transaction_payment_subscriptions.id_transaction')
                    ->leftJoin('transaction_payment_balances', 'transactions.id_transaction', '=', 'transaction_payment_balances.id_transaction')
                    ->where('transactions.id_outlet', $id_oultet)
                    ->whereDate('transactions.transaction_date', $currentDate)
                    ->where('transactions.transaction_payment_status', 'Completed')
                    ->whereNull('reject_at')->get()->toArray();

                $payments = [];
                foreach ($getData as $val){
                    $paymentType = '';
                    $payment = '';
                    $paymentAmount = 0;
                    if(!empty($val['payment_type'])){
                        $paymentType = 'Midtrans';
                        $payment = $val['payment_type'];
                        $paymentAmount = $val['gross_amount'];
                    }elseif(!empty($val['payment_method'])){
                        $paymentType = 'Ipay88';
                        $payment = $val['payment_method'];
                        $paymentAmount = $val['amount'];
                    }elseif(!empty($val['id_transaction_payment_shopee_pay'])){
                        $paymentType = 'Shopeepay';
                        $payment = 'Shopee Pay';
                        $paymentAmount = $val['shopee_amount']/100;
                    }

                    if(!empty($paymentType) && !empty($payment)){
                        $check = array_search($paymentType.'_'.$payment, array_column($payments, 'key'));
                        if($check === false){
                            $payments[] = [
                                'key' => $paymentType.'_'.$payment,
                                'payment_type' => $paymentType,
                                'trx_payment' => $payment,
                                'total_amount' => $paymentAmount
                            ];
                        }else{
                            $payments[$check]['total_amount'] = $payments[$check]['total_amount'] + $paymentAmount;
                        }
                    }


                    if(!empty($val['subscription_nominal'])){
                        $check = array_search('Subscription_Subscription', array_column($payments, 'key'));
                        if($check === false){
                            $payments[] = [
                                'key' => 'Subscription_Subscription',
                                'payment_type' => 'Subscription',
                                'trx_payment' => 'Subscription',
                                'total_amount' => $val['subscription_nominal']
                            ];
                        }else{
                            $payments[$check]['total_amount'] = $payments[$check]['total_amount'] + $val['subscription_nominal'];
                        }
                    }

                    if(!empty($val['balance_nominal'])){
                        $check = array_search('Jiwa Poin_Jiwa Poin', array_column($payments, 'key'));
                        if($check === false){
                            $payments[] = [
                                'key' => 'Jiwa Poin_Jiwa Poin',
                                'payment_type' => 'Jiwa Poin',
                                'trx_payment' => 'Jiwa Poin',
                                'total_amount' => $val['balance_nominal']
                            ];
                        }else{
                            $payments[$check]['total_amount'] = $payments[$check]['total_amount'] + $val['balance_nominal'];
                        }
                    }
                }
            }

            //merge data
            foreach ($listPayment as $val){
                $paymentType = $val['payment_type'];
                if(is_null($val['payment_type'])){
                    $paymentType = $val['trx_payment'];
                }
                $payment = $val['trx_payment'];

                $check = array_search($paymentType.'_'.$payment, array_column($payments, 'key'));
                if($check === false){
                    $payments[] = [
                        'key' => $paymentType.'_'.$payment,
                        'payment_type' => $paymentType,
                        'trx_payment' => $payment,
                        'total_amount' => 0
                    ];
                }
            }
            return response()->json(MyHelper::checkGet($payments));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function summaryDetailPaymentMethod(Request $request){
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultet::where('id_user_franchise' , auth()->user()->id_user_franchise)->first()['id_outlet']??NULL;

        if($id_oultet){
            $list = Transaction::join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
                ->join('users','users.id','=','transactions.id_user')
                ->select('transactions.transaction_grandtotal', 'transactions.transaction_receipt_number', 'transaction_pickups.order_id', 'transactions.id_transaction', 'transactions.transaction_date', 'users.name')
                ->where('transactions.id_outlet', $id_oultet)
                ->where('transactions.transaction_payment_status', 'Completed')
                ->whereNull('reject_at');

            if(strtolower($post['payment_type']) == 'midtrans'){
                $list = $list->addSelect('transaction_payment_midtrans.gross_amount as amount')
                        ->where('payment_type', $post['trx_payment'])
                        ->join('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction');
            }elseif (strtolower($post['payment_type']) == 'ipay88'){
                $list = $list->addSelect('transaction_payment_ipay88s.amount')
                    ->where('payment_method', $post['trx_payment'])
                    ->join('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction');
            }elseif (strtolower($post['payment_type']) == 'shopeepay'){
                $list = $list->addSelect('transaction_payment_shopee_pays.subscription_nominal as amount')
                    ->join('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction');
            }elseif (strtolower($post['payment_type']) == 'subscription'){
                $list = $list->addSelect('transaction_payment_subscriptions.subscription_nominal as amount')
                    ->join('transaction_payment_subscriptions', 'transactions.id_transaction', '=', 'transaction_payment_subscriptions.id_transaction');
            }elseif (strtolower($post['payment_type']) == 'jiwa poin'){
                $list = $list->addSelect('transaction_payment_balances.balance_nominal as amount')
                    ->join('transaction_payment_balances', 'transactions.id_transaction', '=', 'transaction_payment_balances.id_transaction');
            }

            if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
                $dateStart = date('Y-m-d', strtotime($post['date_start']));
                $dateEnd = date('Y-m-d', strtotime($post['date_end']));
                $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
            }else{
                $currentDate = date('Y-m-d');
                $list = $list->whereDate('transactions.transaction_date', $currentDate);
            }

            $list = $list->paginate(30);
            return response()->json(MyHelper::checkGet($list));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    public function summaryChart(Request $request){
        $post = $request->json()->all();

        $id_oultet = UserFranchiseOultet::where('id_user_franchise' , auth()->user()->id_user_franchise)->first()['id_outlet']??NULL;

        if($id_oultet) {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $payments = DailyReportPayment::where('id_outlet', $id_oultet)
                ->where('refund_with_point', 0)
                ->groupBy('payment_type')->groupBy('trx_payment')
                ->select('payment_type', 'trx_payment')
                ->get()->toArray();
            $data = [];
            $date = [];
            foreach ($payments as $payment){
                $begin = new DateTime($dateStart);
                $end   = new DateTime($dateEnd);

                $get = DailyReportPayment::where('refund_with_point', 0)
                        ->where('payment_type', $payment['payment_type'])->where('trx_payment', $payment['trx_payment'])
                        ->where('trx_date', '>=', $dateStart)
                        ->where('trx_date', '<=', $dateEnd)
                        ->select(DB::raw('(CASE WHEN payment_type IS NULL THEN trx_payment
                            ELSE payment_type END) as payment_type'), 'trx_payment', 'trx_date', 'trx_payment_nominal as amount')
                        ->get()->toArray();

                $tmp = [];
                for($i = $begin; $i <= $end; $i->modify('+1 day')){
                    $date[] = $i->format("Y-m-d");
                    $check = array_search($i->format("Y-m-d")." 00:00:00", array_column($get, 'trx_date'));
                    if($check !== false){
                        $tmp[] = (int)$get[$check]['amount'];
                    }else{
                        $tmp[] = 0;
                    }
                }
                $data[] = [
                    'name' => (is_null($payment['payment_type'])? $payment['trx_payment'] : $payment['trx_payment'].'('.$payment['payment_type'].')'),
                    'data' => $tmp
                ];
            }

            $result = [
                'series' => $data,
                'date' => array_unique($date)
            ];
            return response()->json(MyHelper::checkGet($result));
        }
        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }
}
