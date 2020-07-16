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

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\IPay88\Entities\SubscriptionPaymentIpay88;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Modules\Subscription\Entities\SubscriptionPaymentMidtran;
use Modules\Subscription\Entities\SubscriptionPaymentOvo;
use Validator;
use Hash;
use DB;
use Mail;


class ApiReportPayment extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
    public function getReportMidtrans(Request $request){
        $post = $request->json()->all();

        $deals = DealsPaymentMidtran::join('deals_users', 'deals_users.id_deals_user', 'deals_payment_midtrans.id_deals_user')
            ->join('users', 'users.id', 'deals_users.id_user')
            ->selectRaw("payment_type, deals_payment_midtrans.id_deals AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Deals' AS type, deals_payment_midtrans.created_at, deals_users.`voucher_price_cash` AS grand_total, gross_amount, users.name, users.phone, users.email");
        $subscription = SubscriptionPaymentMidtran::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_payment_midtrans.id_subscription_user')
            ->join('users', 'users.id', 'subscription_users.id_user')
            ->selectRaw("payment_type, subscription_payment_midtrans.id_subscription AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Subscription' AS type, subscription_payment_midtrans.created_at, subscription_users.`subscription_price_cash` AS grand_total, gross_amount, users.name, users.phone, users.email");

        $trx = TransactionPaymentMidtran::join('transactions', 'transactions.id_transaction', 'transaction_payment_midtrans.id_transaction')
            ->join('users', 'users.id', 'transactions.id_user')
            ->selectRaw("payment_type,  transactions.id_transaction AS id_report, transactions.trasaction_type AS trx_type, transactions.transaction_receipt_number AS receipt_number, 'Transaction' AS type, transaction_payment_midtrans.created_at, transactions.`transaction_grandtotal` AS grand_total, gross_amount, users.name, users.phone, users.email")
            ->orderBy('created_at', 'desc');

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
                if(in_array('transaction_receipt_number', $arrSubjectUnique) && count($arrSubject) == 1){
                    $unionWithTrx = 1;

                    $trx = $trx->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
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
                    $unionWithDeals = 1;
                    $unionWithSubscription = 1;
                    $unionWithTrx = 1;

                    $deals = $deals->where(function ($subquery) use ($post){
                        foreach ($post['conditions'] as $row){
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

        if(isset($post['export']) && $post['export'] == 1){
            $data = $data->get()->toArray();
        }else{
            $data = $data->paginate(30);
        }
        return response()->json(MyHelper::checkGet($data));
    }

    public function getReportIpay88(Request $request){
        $post = $request->json()->all();

        $deals = DealsPaymentIpay88::join('deals_users', 'deals_users.id_deals_user', 'deals_payment_ipay88s.id_deals_user')
            ->join('users', 'users.id', 'deals_users.id_user')
            ->selectRaw("deals_payment_ipay88s.payment_method as payment_type, deals_payment_ipay88s.id_deals AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Deals' AS type, deals_payment_ipay88s.created_at, deals_users.`voucher_price_cash` AS grand_total, amount as gross_amount, users.name, users.phone, users.email");
        $subscription = SubscriptionPaymentIpay88::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_payment_ipay88s.id_subscription_user')
            ->join('users', 'users.id', 'subscription_users.id_user')
            ->selectRaw("subscription_payment_ipay88s.payment_method as payment_type, subscription_payment_ipay88s.id_subscription AS id_report, NULL AS trx_type, NULL AS receipt_number, 'Subscription' AS type, subscription_payment_ipay88s.created_at, subscription_users.`subscription_price_cash` AS grand_total, amount as gross_amount, users.name, users.phone, users.email");

        $data = TransactionPaymentIpay88::join('transactions', 'transactions.id_transaction', 'transaction_payment_ipay88s.id_transaction')
            ->join('users', 'users.id', 'transactions.id_user')
            ->unionAll($deals)
            ->unionAll($subscription)
            ->selectRaw("transaction_payment_ipay88s.payment_method as payment_type,  transactions.id_transaction AS id_report, transactions.trasaction_type AS trx_type, transactions.transaction_receipt_number AS receipt_number, 'Transaction' AS type, transaction_payment_ipay88s.created_at, transactions.`transaction_grandtotal` AS grand_total, amount as gross_amount, users.name, users.phone, users.email")
            ->orderBy('created_at', 'desc');

        if(isset($post['export']) && $post['export'] == 1){
            $data = $data->get()->toArray();
        }else{
            $data = $data->paginate(30);
        }

        return response()->json(MyHelper::checkGet($data));
    }
}
