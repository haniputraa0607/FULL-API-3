<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\Membership;

use App\Http\Models\LogBalance;
use App\Http\Models\LogTopup;
use App\Http\Models\UsersMembership;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\LogTopupMidtrans;

use Modules\Deals\Http\Requests\Deals\Voucher;
use Modules\Deals\Http\Requests\Claim\Paid;

use Illuminate\Support\Facades\Schema;
use DB;

class BalanceController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->notif   = "Modules\Transaction\Http\Controllers\ApiNotification";
    }

    /* REQUEST */
    function requestCashBackBalance(Request $request) 
    {
        $balance = $this->balance("add", $request->user()->id, null, 800, "Transaction", 50000);
    }

    /* REQUEST */
    function requestTopUpBalance(Request $request) 
    {
        return $this->topUp($request->user()->id, 1339800, 25);
    }

    /* REQUEST */
    function requestPoint(Request $request) 
    {
        // $point = CalculatePoint::calculate($request->user()->id);
        $point = Membership::calculate(null, '083847090002');


        print_r($point);
    }

    /* ADD BALANCE */
    function balance($type, $id_user, $id_reference=null, $balance, $source=null, $grandTotal) {

        $data['id_user']                                = $id_user;
        $data['balance']                                = $balance;
        !is_null($id_reference) ? $data['id_reference'] = $id_reference : null;
        $data['grand_total']                            = $grandTotal;
        $data['source']                                 = $source;
        $data['ccashback_conversion']                   = 0;
        $data['membership_cashback_percentage']         = 0;

        if ($type != "topup") {
            $data['ccashback_conversion'] = $this->getSetting('cashback_conversion_value')->cashback_conversion_value;

            // membership
            $cekMembership                             = $this->getMembershipDetail($id_user);

            if ($cekMembership) {
                $data['membership_level']               = $cekMembership->membership->membership_name;
                $data['membership_cashback_percentage'] = $cekMembership->benefit_cashback_multiplier;
            }
        }

        $save = LogBalance::updateOrCreate(['id_user' => $data['id_user'], 'id_reference' => $data['id_reference'], 'source' => $data['source']], $data);
        
        return $save;
    }

    /* BALANCE NOW */
    function balanceNow($id_user) {
        return LogBalance::where('id_user', $id_user)->sum('balance');
    }

    /* CHECK MEMBERSHIP*/
    function getMembershipDetail($id_user) {
        $member = UsersMembership::where('id_user', $id_user)->with(['membership'])->first();

        return $member;
    }

    /* SETTING */
    function getSetting($key) {
        $setting = Setting::where('key', $key)->first();

        return $setting;
    }

    /* TOPUP */
    function topUp($id_user, $grandTotal, $idTrx=null, $addNominal=null) {
        $data = [];
        $data['id_user'] = $id_user;

        if (!is_null($idTrx)) {
            $data['transaction_reference'] = $idTrx;
        }

        $data['balance_before'] = $this->balanceNow($id_user);

        if ($data['balance_before'] >= $grandTotal) {

            if (!is_null($idTrx)) {
                $dataTrx = Transaction::where('id_transaction', $idTrx)->first();

                if (empty($dataTrx)) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['transaction not found']
                    ];
                }

                $balanceNotif = app($this->notif)->balanceNotif($dataTrx);
                // return response()->json($balanceNotif);
                if ($balanceNotif) {
                    $update = Transaction::where('id_transaction', $dataTrx['id_transaction'])->update(['transaction_payment_status' => 'Completed']);

                    if (!$update) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['fail to create transaction']
                        ];
                    }

                    $dataCheck = Transaction::where('id_transaction', $idTrx)->first();

                    return [
                        'status'   => 'success',
                        'type'     => 'no_topup',
                        'result'   => $dataCheck
                    ];
                }
            }
        } else {
            $data['topup_value']    = $grandTotal - $data['balance_before'];
            $data['balance_after']  = $grandTotal;

            DB::beginTransaction();
            $saveTopUp = LogTopup::create($data);
            if ($saveTopUp) {
                DB::commit();
                return ['status' => 'success', 'type' => 'topup'];
                // $midtrans = $this->midtrans($saveTopUp);
                // if ($midtrans) {
                //     DB::commit();
                //     return $midtrans;
                // }
            }
            
            DB::rollback();
            return [
                'status'   => 'fail',
                'messages' => ['fail to save topup']
            ];
        }
    }

    /* HIT MITRANDS */
    function midtrans($saveTopUp) {
        $check = Transaction::where('id_transaction', $saveTopUp['transaction_reference'])->first();
        $tembakMitrans = Midtrans::token($check['transaction_receipt_number'], $saveTopUp['topup_value']);

        if (isset($tembakMitrans['token'])) {
            // save log midtrans 
            if ($this->saveMidtransTopUp($saveTopUp)) {
                return [
                    'status'   => 'waiting',
                    'midtrans' => $tembakMitrans,
                    'topup'    => $saveTopUp
                ];
            }
        }

        return false;
    }

    /* SAVE LOG MIDTRANS TOPUP */
    function saveMidtransTopUp($saveTopUp) {
        $trx = Transaction::where('id_transaction', $saveTopUp->transaction_reference)->first();

        if ($trx) {
            $midtrans = [
                'order_id'     => $trx->transaction_receipt_number,
                'gross_amount' => $saveTopUp->topup_value,
                'id_log_topup' => $saveTopUp->id_log_topup
            ];

            $save = LogTopupMidtrans::create($midtrans);
            return $save;
        }
        return false;        
    }

    /* ADD TOPUP TO BALANCE */
    function addTopupToBalance($id_log_topup) {
        $dataTopUp = LogTopup::where('id_log_topup', $id_log_topup)->first();

        if ($dataTopUp) {
            // Data IN
            $dataIn = $this->balance("topup", $dataTopUp->id_user, $dataTopUp->transaction_reference, $dataTopUp->topup_value, "Transaction Topup", $dataTopUp->balance_after);

            if ($dataIn) {
                // Data OUT
                $dataOut = $this->balance("topup", $dataTopUp->id_user, $dataTopUp->transaction_reference, - $dataTopUp->balance_after, "Transaction", $dataTopUp->balance_after);

                if ($dataOut) {
                    return [
                        'status' => 'success'
                    ];
                }
            }
        }

        return false;
    }
}
