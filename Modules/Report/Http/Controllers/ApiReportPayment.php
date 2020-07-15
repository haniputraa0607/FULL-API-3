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

        $data = TransactionPaymentMidtran::join('transactions', 'transactions.id_transaction', 'transaction_payment_midtrans.id_transaction')
            ->join('users', 'users.id', 'transactions.id_user')
            ->unionAll($deals)
            ->unionAll($subscription)
            ->selectRaw("payment_type,  transactions.id_transaction AS id_report, transactions.trasaction_type AS trx_type, transactions.transaction_receipt_number AS receipt_number, 'Transaction' AS type, transaction_payment_midtrans.created_at, transactions.`transaction_grandtotal` AS grand_total, gross_amount, users.name, users.phone, users.email")
            ->orderBy('created_at', 'desc')
            ->paginate(30);

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
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json(MyHelper::checkGet($data));
    }
}
