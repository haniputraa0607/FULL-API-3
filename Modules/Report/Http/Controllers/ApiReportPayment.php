<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsPaymentOvo;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Treatment;
use App\Http\Models\Consultation;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\Reservation;
use App\Http\Models\LogActivitiesApps;
use App\Http\Models\Product;

use App\Jobs\ExportJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\IPay88\Entities\SubscriptionPaymentIpay88;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Report\Entities\DailyReportPayment;
use Modules\Report\Entities\DailyReportPaymentDeals;
use Modules\Report\Entities\DailyReportPaymentSubcription;
use Modules\Report\Entities\ExportQueue;
use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Modules\Subscription\Entities\SubscriptionPaymentMidtran;
use Modules\Subscription\Entities\SubscriptionPaymentOvo;
use Validator;
use Hash;
use DB;
use Mail;
use File;

class ApiReportPayment extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
    public function getReportMidtrans(Request $request){
        $post = $request->json()->all();

        $filter = $this->filterMidtrans($post);

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $sum = $filter->sum('gross_amount');
        }else{
            $dealsSum = DailyReportPaymentDeals::where('payment_type', 'Midtrans');
            $trxSum = DailyReportPayment::where('payment_type', 'Midtrans');
            $subSum = DailyReportPaymentSubcription::where('payment_type', 'Midtrans');

            if(isset($post['date_start']) && !empty($post['date_start']) &&
                isset($post['date_end']) && !empty($post['date_end'])){
                $start_date = date('Y-m-d', strtotime($post['date_start']));
                $end_date = date('Y-m-d', strtotime($post['date_end']));

                $dealsSum = $dealsSum->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
                $trxSum = $trxSum->whereDate('trx_date', '>=', $start_date)->whereDate('trx_date', '<=', $end_date);
                $subSum = $subSum->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
            }

            $sum = $dealsSum->sum('payment_nominal') + $trxSum->sum('trx_payment_nominal') + $subSum->sum('payment_nominal');
        }

        $data = $filter->orderBy('created_at', 'desc')->paginate(30);

        if($data){
            $result = [
                'status' => 'success',
                'result' => [
                    'data' => $data,
                    'sum' => $sum
                ]
            ];

            return response()->json($result);
        }else{
            return response()->json(MyHelper::checkGet($data));
        }
    }

    public function filterMidtrans($post){
        $deals = DealsPaymentMidtran::join('deals_users', 'deals_users.id_deals_user', 'deals_payment_midtrans.id_deals_user')
            ->leftJoin('users', 'users.id', 'deals_users.id_user')
            ->selectRaw("deals_users.paid_status as payment_status, payment_type, deals_payment_midtrans.id_deals AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Deals' AS type, deals_payment_midtrans.created_at, deals_users.`voucher_price_cash` AS grand_total, gross_amount, users.name, users.phone, users.email");
        $subscription = SubscriptionPaymentMidtran::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_payment_midtrans.id_subscription_user')
            ->leftJoin('users', 'users.id', 'subscription_users.id_user')
            ->selectRaw("subscription_users.paid_status as payment_status, payment_type, subscription_payment_midtrans.id_subscription AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Subscription' AS type, subscription_payment_midtrans.created_at, subscription_users.`subscription_price_cash` AS grand_total, gross_amount, users.name, users.phone, users.email");

        $trx = TransactionPaymentMidtran::join('transactions', 'transactions.id_transaction', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('users', 'users.id', 'transactions.id_user')
            ->selectRaw("transactions.transaction_payment_status as payment_status, payment_type,  transactions.id_transaction AS id_report, transactions.trasaction_type AS trx_type, transactions.transaction_receipt_number AS receipt_number, 'Transaction' AS type, transaction_payment_midtrans.created_at, transactions.`transaction_grandtotal` AS grand_total, gross_amount, users.name, users.phone, users.email");

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $deals = $deals->whereDate('deals_payment_midtrans.created_at', '>=', $start_date)
                ->whereDate('deals_payment_midtrans.created_at', '<=', $end_date);
            $subscription = $subscription->whereDate('subscription_payment_midtrans.created_at', '>=', $start_date)
                ->whereDate('subscription_payment_midtrans.created_at', '<=', $end_date);
            $trx = $trx->whereDate('transaction_payment_midtrans.created_at', '>=', $start_date)
                ->whereDate('transaction_payment_midtrans.created_at', '<=', $end_date);
        }

        $unionWithDeals = 1;
        $unionWithSubscription = 1;
        $unionWithTrx = 1;

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $checkFilterStatus = array_search('status', array_column($post['conditions'], 'subject'));
            if($checkFilterStatus === false){
                $deals = $deals->where('deals_users.paid_status', 'Completed');
                $subscription = $subscription->where('subscription_users.paid_status', 'Completed');
                $trx = $trx->where('transactions.transaction_payment_status', 'Completed');
            }

            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(is_object($row)){
                        $row = (array)$row;
                    }
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            $deals = $deals->where('deals_users.paid_status', $row['operator']);
                            $subscription = $subscription->where('subscription_users.paid_status', $row['operator']);
                            $trx = $trx->where('transactions.transaction_payment_status', $row['operator']);
                        }

                        if($row['subject'] == 'type'){
                            if($row['operator'] == 'Deals'){
                                $unionWithSubscription = 0;
                                $unionWithTrx = 0;
                            }elseif($row['operator'] == 'Subscription'){
                                $unionWithDeals = 0;
                                $unionWithTrx = 0;
                            }elseif($row['operator'] == 'Transaction'){
                                $unionWithDeals = 0;
                                $unionWithSubscription = 0;
                            }
                        }

                        if($row['subject'] == 'name'){
                            if($row['operator'] == '='){
                                $deals = $deals->where('users.name', $row['parameter']);
                                $subscription = $subscription->where('users.name', $row['parameter']);
                                $trx = $trx->where('users.name', $row['parameter']);
                            }else{
                                $deals = $deals->where('users.name', 'like', '%'.$row['parameter'].'%');
                                $subscription = $subscription->where('users.name', 'like', '%'.$row['parameter'].'%');
                                $trx = $trx->where('users.name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone'){
                            if($row['operator'] == '='){
                                $deals = $deals->where('users.phone', $row['parameter']);
                                $subscription = $subscription->where('users.phone', $row['parameter']);
                                $trx = $trx->where('users.phone', $row['parameter']);
                            }else{
                                $deals = $deals->where('users.phone', 'like', '%'.$row['parameter'].'%');
                                $subscription = $subscription->where('users.phone', 'like', '%'.$row['parameter'].'%');
                                $trx = $trx->where('users.phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'grandtotal'){
                            $deals = $deals->where('deals_users.voucher_price_cash',$row['operator'] ,$row['parameter']);
                            $subscription = $subscription->where('subscription_users.subscription_price_cash',$row['operator'] ,$row['parameter']);
                            $trx = $trx->where('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                        }

                        if($row['subject'] == 'amount'){
                            $deals = $deals->where('gross_amount',$row['operator'] ,$row['parameter']);
                            $subscription = $subscription->where('gross_amount',$row['operator'] ,$row['parameter']);
                            $trx = $trx->where('gross_amount',$row['operator'] ,$row['parameter']);
                        }

                        if($row['subject'] == 'transaction_receipt_number'){
                            $unionWithDeals = 0;
                            $unionWithSubscription = 0;
                            if($row['operator'] == '='){
                                $trx = $trx->where('transactions.transaction_receipt_number',$row['parameter']);
                            }else{
                                $trx = $trx->where('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            }else{
                $unionWithDeals = 0;
                $unionWithSubscription = 0;
                $unionWithTrx = 0;

                $arrSubject = array_column($post['conditions'], 'subject');
                $arrSubjectUnique = array_unique($arrSubject);

                $arrOperator = array_column($post['conditions'], 'operator');
                $arrOperatorUnique = array_unique($arrOperator);

                if(in_array('transaction_receipt_number', $arrSubjectUnique) && count($arrSubject) == 1){
                    $unionWithTrx = 1;

                    $trx = $trx->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('transactions.transaction_payment_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhere('gross_amount',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'transaction_receipt_number'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number',$row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                    }
                                }
                            }
                        }
                    });
                }else{
                    if(in_array('Deals', $arrOperatorUnique))$unionWithDeals = 1;
                    if(in_array('Subscription', $arrOperatorUnique))$unionWithSubscription = 1;
                    if(in_array('Transaction', $arrOperatorUnique))$unionWithTrx = 1;
                    if(in_array('transaction_receipt_number', $arrSubjectUnique))$unionWithTrx = 1;
                    if(!in_array('Deals', $arrOperatorUnique) && !in_array('Subscription', $arrOperatorUnique) && !in_array('Transaction', $arrOperatorUnique)){
                        $unionWithDeals = 1;
                        $unionWithSubscription = 1;
                        $unionWithTrx = 1;
                    }

                    $deals = $deals->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('deals_users.paid_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('transactions.voucher_price_cash',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhere('gross_amount',$row['operator'] ,$row['parameter']);
                                }
                            }
                        }
                    });

                    $subscription = $subscription->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('subscription_users.paid_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('subscription_users.subscription_price_cash',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhere('gross_amount',$row['operator'] ,$row['parameter']);
                                }
                            }
                        }
                    });

                    $trx = $trx->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('transactions.transaction_payment_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhere('gross_amount',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'transaction_receipt_number'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number',$row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }else{
            $deals = $deals->where('deals_users.paid_status', 'Completed');
            $subscription = $subscription->where('subscription_users.paid_status', 'Completed');
            $trx = $trx->where('transactions.transaction_payment_status', 'Completed');
        }

        //union by type user choose
        if($unionWithTrx == 1 && $unionWithDeals == 1 && $unionWithSubscription == 1){
            $data = $trx->unionAll($deals)->unionAll($subscription);
        }elseif($unionWithTrx == 1 && $unionWithDeals == 1 && $unionWithSubscription == 0){
            $data = $trx->unionAll($deals);
        }elseif($unionWithTrx == 1 && $unionWithDeals == 0 && $unionWithSubscription == 0){
            $data = $trx;
        }elseif($unionWithTrx == 0 && $unionWithDeals == 1 && $unionWithSubscription == 1){
            $data = $deals->unionAll($subscription);
        }elseif($unionWithTrx == 0 && $unionWithDeals == 1 && $unionWithSubscription == 0){
            $data = $deals;
        }elseif($unionWithTrx == 1 && $unionWithDeals == 0 && $unionWithSubscription == 1){
            $data = $trx->unionAll($subscription);
        }elseif($unionWithTrx == 0 && $unionWithDeals == 0 && $unionWithSubscription == 1){
            $data = $subscription;
        }

        return $data;
    }

    public function getReportIpay88(Request $request){
        $post = $request->json()->all();

        $filter = $this->filterIpay88($post);

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $sum = $filter->sum('gross_amount');
        }else{
            $dealsSum = DailyReportPaymentDeals::where('payment_type', 'Ipay88');
            $trxSum = DailyReportPayment::where('payment_type', 'Ipay88');
            $subSum = DailyReportPaymentSubcription::where('payment_type', 'Ipay88');

            if(isset($post['date_start']) && !empty($post['date_start']) &&
                isset($post['date_end']) && !empty($post['date_end'])){
                $start_date = date('Y-m-d', strtotime($post['date_start']));
                $end_date = date('Y-m-d', strtotime($post['date_end']));

                $dealsSum = $dealsSum->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
                $trxSum = $trxSum->whereDate('trx_date', '>=', $start_date)->whereDate('trx_date', '<=', $end_date);
                $subSum = $subSum->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date);
            }

            $sum = $dealsSum->sum('payment_nominal') + $trxSum->sum('trx_payment_nominal') + $subSum->sum('payment_nominal');
        }

        $data = $filter->orderBy('created_at', 'desc')->paginate(30);

        if($data){
            $result = [
                'status' => 'success',
                'result' => [
                    'data' => $data,
                    'sum' => $sum
                ]
            ];

            return response()->json($result);
        }else{
            return response()->json(MyHelper::checkGet($data));
        }
    }

    public function filterIpay88($post){

        $deals = DealsPaymentIpay88::join('deals_users', 'deals_users.id_deals_user', 'deals_payment_ipay88s.id_deals_user')
            ->leftJoin('users', 'users.id', 'deals_users.id_user')
            ->selectRaw("deals_users.paid_status as payment_status, deals_payment_ipay88s.payment_method as payment_type, deals_payment_ipay88s.id_deals AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Deals' AS type, deals_payment_ipay88s.created_at, deals_users.`voucher_price_cash` AS grand_total, (amount/100) as gross_amount, users.name, users.phone, users.email");
        $subscription = SubscriptionPaymentIpay88::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_payment_ipay88s.id_subscription_user')
            ->leftJoin('users', 'users.id', 'subscription_users.id_user')
            ->selectRaw("subscription_users.paid_status as payment_status, subscription_payment_ipay88s.payment_method as payment_type, subscription_payment_ipay88s.id_subscription AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Subscription' AS type, subscription_payment_ipay88s.created_at, subscription_users.`subscription_price_cash` AS grand_total, (amount/100) as gross_amount, users.name, users.phone, users.email");
        $trx = TransactionPaymentIpay88::join('transactions', 'transactions.id_transaction', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('users', 'users.id', 'transactions.id_user')
            ->selectRaw("transactions.transaction_payment_status as payment_status, transaction_payment_ipay88s.payment_method as payment_type,  transactions.id_transaction AS id_report, transactions.trasaction_type AS trx_type, transactions.transaction_receipt_number AS receipt_number, 'Transaction' AS type, transaction_payment_ipay88s.created_at, transactions.`transaction_grandtotal` AS grand_total, (amount/100) as gross_amount, users.name, users.phone, users.email")
            ->orderBy('created_at', 'desc');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $deals = $deals->whereDate('deals_payment_ipay88s.created_at', '>=', $start_date)
                ->whereDate('deals_payment_ipay88s.created_at', '<=', $end_date);
            $subscription = $subscription->whereDate('subscription_payment_ipay88s.created_at', '>=', $start_date)
                ->whereDate('subscription_payment_ipay88s.created_at', '<=', $end_date);
            $trx = $trx->whereDate('transaction_payment_ipay88s.created_at', '>=', $start_date)
                ->whereDate('transaction_payment_ipay88s.created_at', '<=', $end_date);
        }

        $unionWithDeals = 1;
        $unionWithSubscription = 1;
        $unionWithTrx = 1;

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $checkFilterStatus = array_search('status', array_column($post['conditions'], 'subject'));
            if($checkFilterStatus === false){
                $deals = $deals->where('deals_users.paid_status', 'Completed');
                $subscription = $subscription->where('subscription_users.paid_status', 'Completed');
                $trx = $trx->where('transactions.transaction_payment_status', 'Completed');
            }

            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(is_object($row)){
                        $row = (array)$row;
                    }
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            $deals = $deals->where('deals_users.paid_status', $row['operator']);
                            $subscription = $subscription->where('subscription_users.paid_status', $row['operator']);
                            $trx = $trx->where('transactions.transaction_payment_status', $row['operator']);
                        }

                        if($row['subject'] == 'type'){
                            if($row['operator'] == 'Deals'){
                                $unionWithSubscription = 0;
                                $unionWithTrx = 0;
                            }elseif($row['operator'] == 'Subscription'){
                                $unionWithDeals = 0;
                                $unionWithTrx = 0;
                            }elseif($row['operator'] == 'Transaction'){
                                $unionWithDeals = 0;
                                $unionWithSubscription = 0;
                            }
                        }

                        if($row['subject'] == 'name'){
                            if($row['operator'] == '='){
                                $deals = $deals->where('users.name', $row['parameter']);
                                $subscription = $subscription->where('users.name', $row['parameter']);
                                $trx = $trx->where('users.name', $row['parameter']);
                            }else{
                                $deals = $deals->where('users.name', 'like', '%'.$row['parameter'].'%');
                                $subscription = $subscription->where('users.name', 'like', '%'.$row['parameter'].'%');
                                $trx = $trx->where('users.name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone'){
                            if($row['operator'] == '='){
                                $deals = $deals->where('users.phone', $row['parameter']);
                                $subscription = $subscription->where('users.phone', $row['parameter']);
                                $trx = $trx->where('users.phone', $row['parameter']);
                            }else{
                                $deals = $deals->where('users.phone', 'like', '%'.$row['parameter'].'%');
                                $subscription = $subscription->where('users.phone', 'like', '%'.$row['parameter'].'%');
                                $trx = $trx->where('users.phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'grandtotal'){
                            $deals = $deals->where('deals_users.voucher_price_cash',$row['operator'] ,$row['parameter']);
                            $subscription = $subscription->where('subscription_users.subscription_price_cash',$row['operator'] ,$row['parameter']);
                            $trx = $trx->where('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                        }

                        if($row['subject'] == 'amount'){
                            $deals = $deals->whereRaw('(amount/100) '. $row['operator'].' '.$row['parameter']);
                            $subscription = $subscription->whereRaw('(amount/100) '. $row['operator'].' '.$row['parameter']);
                            $trx = $trx->whereRaw('(amount/100) '. $row['operator'].' '.$row['parameter']);
                        }

                        if($row['subject'] == 'transaction_receipt_number'){
                            $unionWithDeals = 0;
                            $unionWithSubscription = 0;
                            if($row['operator'] == '='){
                                $trx = $trx->where('transactions.transaction_receipt_number',$row['parameter']);
                            }else{
                                $trx = $trx->where('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            }else{
                $unionWithDeals = 0;
                $unionWithSubscription = 0;
                $unionWithTrx = 0;

                $arrSubject = array_column($post['conditions'], 'subject');
                $arrSubjectUnique = array_unique($arrSubject);

                $arrOperator = array_column($post['conditions'], 'operator');
                $arrOperatorUnique = array_unique($arrOperator);

                if(in_array('transaction_receipt_number', $arrSubjectUnique) && count($arrSubject) == 1){
                    $unionWithTrx = 1;

                    $trx = $trx->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('transactions.transaction_payment_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhereRaw('(amount/100) '.$row['operator'].' '.$row['parameter']);
                                }

                                if($row['subject'] == 'transaction_receipt_number'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number',$row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                    }
                                }
                            }
                        }
                    });
                }else{
                    if(in_array('Deals', $arrOperatorUnique))$unionWithDeals = 1;
                    if(in_array('Subscription', $arrOperatorUnique))$unionWithSubscription = 1;
                    if(in_array('Transaction', $arrOperatorUnique))$unionWithTrx = 1;
                    if(in_array('transaction_receipt_number', $arrSubjectUnique))$unionWithTrx = 1;
                    if(!in_array('Deals', $arrOperatorUnique) && !in_array('Subscription', $arrOperatorUnique) && !in_array('Transaction', $arrOperatorUnique)){
                        $unionWithDeals = 1;
                        $unionWithSubscription = 1;
                        $unionWithTrx = 1;
                    }

                    $deals = $deals->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('deals_users.paid_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('transactions.voucher_price_cash',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhereRaw('(amount/100) '.$row['operator'].' '.$row['parameter']);
                                }
                            }
                        }
                    });

                    $subscription = $subscription->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('subscription_users.paid_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('subscription_users.subscription_price_cash',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhere('amount',$row['operator'] ,$row['parameter']);
                                }
                            }
                        }
                    });

                    $trx = $trx->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
                            if(is_object($row)){
                                $row = (array)$row;
                            }
                            if(isset($row['subject'])){
                                if($row['subject'] == 'status'){
                                    $subquery = $subquery->orWhere('transactions.transaction_payment_status', $row['operator']);
                                }

                                if($row['subject'] == 'name'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.name', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'phone'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('users.phone', $row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                    }
                                }

                                if($row['subject'] == 'grandtotal'){
                                    $subquery = $subquery->orWhere('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                                }

                                if($row['subject'] == 'amount'){
                                    $subquery = $subquery->orWhereRaw('(amount/100) '.$row['operator'].' '.$row['parameter']);
                                }

                                if($row['subject'] == 'transaction_receipt_number'){
                                    if($row['operator'] == '='){
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number',$row['parameter']);
                                    }else{
                                        $subquery = $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }else{
            $deals = $deals->where('deals_users.paid_status', 'Completed');
            $subscription = $subscription->where('subscription_users.paid_status', 'Completed');
            $trx = $trx->where('transactions.transaction_payment_status', 'Completed');
        }

        //union by type user choose
        if($unionWithTrx == 1 && $unionWithDeals == 1 && $unionWithSubscription == 1){
            $data = $trx->unionAll($deals)->unionAll($subscription);
        }elseif($unionWithTrx == 1 && $unionWithDeals == 1 && $unionWithSubscription == 0){
            $data = $trx->unionAll($deals);
        }elseif($unionWithTrx == 1 && $unionWithDeals == 0 && $unionWithSubscription == 0){
            $data = $trx;
        }elseif($unionWithTrx == 0 && $unionWithDeals == 1 && $unionWithSubscription == 1){
            $data = $deals->unionAll($subscription);
        }elseif($unionWithTrx == 0 && $unionWithDeals == 1 && $unionWithSubscription == 0){
            $data = $deals;
        }elseif($unionWithTrx == 1 && $unionWithDeals == 0 && $unionWithSubscription == 1){
            $data = $trx->unionAll($subscription);
        }elseif($unionWithTrx == 0 && $unionWithDeals == 0 && $unionWithSubscription == 1){
            $data = $subscription;
        }

        return $data;
    }

    public function exportExcel($filter){
        if(isset($filter['type'])){
            if($filter['type'] == 'ipay88'){
                $data = $this->filterIpay88($filter);
            }elseif ($filter['type'] == 'midtrans'){
                $data = $this->filterMidtrans($filter);
            }

            foreach ($data->cursor() as $val) {

                yield [
                    'Date' => date('d M Y H:i', strtotime($val['created_at'])),
                    'Status' => $val['payment_status'],
                    'Type' => $val['type'],
                    'Payment Type' => $val['payment_type'],
                    'Grand Total' => $val['grand_total'],
                    'Payment Amount' => (int)$val['gross_amount'],
                    'User Name' => $val['name'],
                    'User Phone' => $val['phone'],
                    'User Email' => $val['email'],
                    'Receipt Number' => $val['receipt_number']
                ];
            }
        }
    }
}
