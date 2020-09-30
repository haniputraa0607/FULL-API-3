<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Models\LogBalance;
use App\Http\Models\User;
use App\Lib\Midtrans;
use App\Lib\MyHelper;
use DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\IPay88\Entities\SubscriptionPaymentIpay88;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPaymentMidtran;
use Modules\Subscription\Entities\SubscriptionUser;

class ApiCronSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->subscription_claim = "Modules\Subscription\Http\Controllers\ApiSubscriptionClaim";
        $this->balance            = "Modules\Balance\Http\Controllers\BalanceController";
    }

    /**
     * Cancel expired claim subscription transaction
     * @return Response
     */
    public function cancel()
    {
        $log = MyHelper::logCron('Cancel Subscription');
        try {
            $now     = date('Y-m-d H:i:s');
            $expired = date('Y-m-d H:i:s', strtotime('- 5minutes'));

            $getTrx = SubscriptionUser::where('paid_status', 'Pending')->where('bought_at', '<=', $expired)->get();

            if (empty($getTrx)) {
                $log->success('empty');
                return response()->json(['empty']);
            }
            $count = 0;
            foreach ($getTrx as $key => $singleTrx) {

                $user = User::where('id', $singleTrx->id_user)->first();
                if (empty($user)) {
                    continue;
                }
                if ($singleTrx->payment_method == 'Midtrans') {
                    $trx_mid = SubscriptionPaymentMidtran::where('id_subscription_user', $singleTrx->id_subscription_user)->first();
                    if ($trx_mid) {
                        $midtransStatus = Midtrans::status($trx_mid->order_id, 'subscription');
                        if (in_array(($midtransStatus['response']['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure']) || $midtransStatus['status_code'] == '404') {
                            $connectMidtrans = Midtrans::expire($trx_mid->order_id);
                        } else {
                            continue;
                        }
                    }
                } elseif ($singleTrx->payment_method == 'Ipay88') {
                    $trx_ipay = SubscriptionPaymentIpay88::where('id_subscription_user', $singleTrx->id_subscription_user)->first();

                    if (strtolower($trx_ipay->payment_method) == 'credit card' && $singleTrx->bought_at > date('Y-m-d H:i:s', strtotime('- 15minutes'))) {
                        continue;
                    }

                    $update   = \Modules\IPay88\Lib\IPay88::create()->update($trx_ipay ?: $singleTrx->id_subscription_user, [
                        'type'             => 'subscription',
                        'Status'           => '0',
                        'requery_response' => 'Cancelled by cron',
                    ], false, false);
                    continue;
                }
                // $detail = $this->getHtml($singleTrx, $productTrx, $user->name, $user->phone, $singleTrx->created_at, $singleTrx->transaction_receipt_number);

                // $autoCrm = app($this->autocrm)->SendAutoCRM('Transaction Online Cancel', $user->phone, ['date' => $singleTrx->created_at, 'status' => $singleTrx->transaction_payment_status, 'name'  => $user->name, 'id' => $singleTrx->transaction_receipt_number, 'receipt' => $detail, 'id_reference' => $singleTrx->transaction_receipt_number]);
                // if (!$autoCrm) {
                //     continue;
                // }

                DB::beginTransaction();

                $singleTrx->paid_status = 'Cancelled';
                $singleTrx->void_date   = date('Y-m-d H:i:s');
                $singleTrx->save();

                if (!$singleTrx) {
                    DB::rollBack();
                    continue;
                }
                // revert back subscription data
                $subscription = Subscription::where('id_subscription', $singleTrx->id_subscription)->first();
                if ($subscription) {
                    $up1 = $subscription->update(['subscription_bought' => $subscription->subscription_bought - 1]);
                    if (!$up1) {
                        DB::rollBack();
                        continue;
                    }
                }
                // $up2 = SubscriptionUserVoucher::where('id_subscription_user_voucher', $singleTrx->id_subscription_user_voucher)->delete();
                // if (!$up2) {
                //     DB::rollBack();
                //     continue;
                // }
                //reversal balance
                $logBalance = LogBalance::where('id_reference', $singleTrx->id_subscription_user)->where('source', 'Subscription Balance')->where('balance', '<', 0)->get();
                foreach ($logBalance as $logB) {
                    $reversal = app($this->balance)->addLogBalance($singleTrx->id_user, abs($logB['balance']), $singleTrx->id_subscription_user, 'Subscription Reversal', $singleTrx->subscription_price_point ?: $singleTrx->subscription_price_cash);
                    if (!$reversal) {
                        DB::rollBack();
                        continue;
                    }
                    // $usere= User::where('id',$singleTrx->id_user)->first();
                    // $send = app($this->autocrm)->SendAutoCRM('Transaction Failed Point Refund', $usere->phone,
                    //     [
                    //         "outlet_name"       => $singleTrx->outlet_name->outlet_name,
                    //         "transaction_date"  => $singleTrx->transaction_date,
                    //         'id_transaction'    => $singleTrx->id_transaction,
                    //         'receipt_number'    => $singleTrx->transaction_receipt_number,
                    //         'received_point'    => (string) abs($logB['balance'])
                    //     ]
                    // );
                }

                $count++;
                DB::commit();

            }
            $log->success($count);
            return [$count];
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

}
