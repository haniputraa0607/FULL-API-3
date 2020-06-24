<?php

namespace Modules\Deals\Http\Controllers;

use App\Http\Models\Deal;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\LogBalance;
use App\Http\Models\User;
use App\Lib\Midtrans;
use DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\IPay88\Entities\DealsPaymentIpay88;

class ApiCronDealsController extends Controller
{
    public function __construct()
    {
        $this->deals_claim = "Modules\Deals\Http\Controllers\ApiDealsClaim";
        $this->balance     = "Modules\Balance\Http\Controllers\BalanceController";
    }

    /**
     * Cancel not deals not paid
     * @return Response
     */
    public function cancel()
    {
        $now     = date('Y-m-d H:i:s');
        $expired = date('Y-m-d H:i:s', strtotime('- 15minutes'));

        $getTrx = DealsUser::join('deals_vouchers', 'deals_vouchers.id_deals_voucher', '=', 'deals_users.id_deals_voucher')->where('paid_status', 'Pending')->where('claimed_at', '<=', $expired)->get();

        if (empty($getTrx)) {
            return response()->json(['empty']);
        }
        $count = 0;
        foreach ($getTrx as $key => $singleTrx) {

            $user = User::where('id', $singleTrx->id_user)->first();
            if (empty($user)) {
                continue;
            }
            if ($singleTrx->payment_method == 'Midtrans') {
                $trx_mid = DealsPaymentMidtran::where('id_deals_user', $singleTrx->id_deals_user)->first();
                if ($trx_mid) {
                    $connectMidtrans = Midtrans::expire($trx_mid->order_id);
                }
            } elseif ($singleTrx->payment_method == 'Ipay88') {
                $trx_ipay = DealsPaymentIpay88::where('id_deals_user', $singleTrx->id_deals_user)->first();
                $update   = \Modules\IPay88\Lib\IPay88::create()->update($trx_ipay ?: $singleTrx->id_deals_user, [
                    'type'             => 'deals',
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

            DB::begintransaction();

            $singleTrx->paid_status = 'Cancelled';
            $singleTrx->save();

            if (!$singleTrx) {
                DB::rollBack();
                continue;
            }
            // revert back deals data
            $deals = Deal::where('id_deals', $singleTrx->id_deals)->first();
            if ($deals) {
                $up1 = $deals->update(['deals_total_claimed' => $deals->deals_total_claimed - 1]);
                if (!$up1) {
                    DB::rollBack();
                    continue;
                }
            }
            $up2 = DealsVoucher::where('id_deals_voucher', $singleTrx->id_deals_voucher)->update(['deals_voucher_status' => 'Available']);
            if (!$up2) {
                DB::rollBack();
                continue;
            }
            //reversal balance
            $logBalance = LogBalance::where('id_reference', $singleTrx->id_deals_user)->where('source', 'Deals Balance')->where('balance', '<', 0)->get();
            foreach ($logBalance as $logB) {
                $reversal = app($this->balance)->addLogBalance($singleTrx->id_user, abs($logB['balance']), $singleTrx->id_deals_user, 'Claim Deals Failed', $singleTrx->voucher_price_point ?: $singleTrx->voucher_price_cash);
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
        return [$count];
    }
}
