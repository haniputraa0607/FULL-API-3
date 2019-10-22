<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\Membership;

use App\Http\Models\User;
use App\Http\Models\LogBalance;
use App\Http\Models\LogTopup;
use App\Http\Models\UsersMembership;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\LogTopupMidtrans;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionMultiplePayment;

use Modules\Balance\Http\Controllers\NewTopupController;

use Modules\Deals\Http\Requests\Deals\Voucher;
use Modules\Deals\Http\Requests\Claim\Paid;

use Illuminate\Support\Facades\Schema;
use DB;
use Hash;

class BalanceController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->notif   = "Modules\Transaction\Http\Controllers\ApiNotification";
    }

    /* add log balance, check the hash
     * this function is created as a helper (for another controller),
     * not for api
    */
    public function addLogBalance($id_user, $balance_nominal, $id_reference=null, $source=null, $grand_total=0)
    {
        $user = User::with('memberships')->where('id', $id_user)->first();

        $user_member = $user->toArray();
        $level = null;
        if (!empty($user_member['memberships'][0]['membership_name'])) {
            $level = $user_member['memberships'][0]['membership_name'];
        }
        $cashback_percentage = 0;
        if (isset($user_member['memberships'][0]['benefit_cashback_multiplier'])) {
            $cashback_percentage = $user_member['memberships'][0]['benefit_cashback_multiplier'];
        }

        $setting_cashback = Setting::where('key', 'cashback_conversion_value')->first();
        $balance_before = LogBalance::where('id_user', $id_user)->sum('balance');
        $balance_after = $balance_before + $balance_nominal;

        // check balance data from hashed text
        $newTopupController = new NewTopupController();
        $checkHashBefore = $newTopupController->checkHash('log_balances', $id_user);
        if (!$checkHashBefore) {
            return false;
        }

        DB::beginTransaction();
        $checkLog = LogBalance::where('source', $source)->where('id_reference', $id_reference)->first();
        if($checkLog){
            $balance_before = $checkLog->balance_before;
            if($balance_nominal == $checkLog->balance){
                $balance_after = $checkLog->balance_after;
            }else{
                $balance_after = $balance_before + $balance_nominal; 
            }
        }   

        $LogBalance = [
            'id_user'                        => $id_user,
            'balance'                        => $balance_nominal,
            'balance_before'                 => $balance_before,
            'balance_after'                  => $balance_after,
            'id_reference'                   => $id_reference,
            'source'                         => $source,
            'grand_total'                    => $grand_total,
            'ccashback_conversion'           => $setting_cashback->value,
            'membership_level'               => $level,
            'membership_cashback_percentage' => $cashback_percentage
        ];

       $create = LogBalance::updateOrCreate(['id_user' => $id_user, 'id_reference' => $id_reference, 'source' => $source], $LogBalance);

        // get inserted data to hash
        $log_balance = LogBalance::find($create->id_log_balance);
        // hash the inserted data
        $dataHashBalance = [
            'id_log_balance'                 => $log_balance->id_log_balance,
            'id_user'                        => $log_balance->id_user,
            'balance'                        => $log_balance->balance,
            'balance_before'                 => $log_balance->balance_before,
            'balance_after'                  => $log_balance->balance_after,
            'id_reference'                   => $log_balance->id_reference,
            'source'                         => $log_balance->source,
            'grand_total'                    => $log_balance->grand_total,
            'ccashback_conversion'           => $log_balance->ccashback_conversion,
            'membership_level'               => $log_balance->membership_level,
            'membership_cashback_percentage' => $log_balance->membership_cashback_percentage
        ];
        // $encodeCheck = utf8_encode(json_encode(($dataHashBalance)));
        // $enc = MyHelper::encryptkhususnew($encodeCheck);
        
        // AutoCRM Taruh sini

        $enc = base64_encode(json_encode(($dataHashBalance)));
        // update enc column
        $log_balance->update(['enc' => $enc]);
        
        $new_user_balance = LogBalance::where('id_user', $user->id)->sum('balance');
        $update_user = $user->update(['balance' => $new_user_balance]);

        if (!($log_balance && $update_user)) {
            DB::rollback();
            return false;
        }

        DB::commit();

        return $log_balance;
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
        if ($data['balance_before'] < 1) {
            return [
                'status'   => 'fail',
                'messages' => ['You need more kopi point']
            ];
        }

        if ($data['balance_before'] >= $grandTotal) {

            if (!is_null($idTrx)) {
                $dataTrx = Transaction::where('id_transaction', $idTrx)->with('outlet')->first();

                if (empty($dataTrx)) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['transaction not found']
                    ];
                }

                $balanceNotif = app($this->notif)->balanceNotif($dataTrx);

                if ($balanceNotif) {
                    $update = Transaction::where('id_transaction', $dataTrx['id_transaction'])->update(['transaction_payment_status' => 'Completed']);

                    if (!$update) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['fail to create transaction']
                        ];
                    }

                    $dataCheck = Transaction::where('id_transaction', $idTrx)->first();

                    $dataPaymentBalance = [
                        'id_transaction'  => $idTrx,
                        'balance_nominal' => $grandTotal
                    ];

                    $savePaymentBalance = TransactionPaymentBalance::create($dataPaymentBalance);
                    if (!$savePaymentBalance) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['fail to create transaction']
                        ];
                    }

                    $dataMultiple = [
                        'id_transaction' => $idTrx,
                        'type'           => 'Balance',
                        'id_payment'     => $savePaymentBalance['id_transaction_payment_balance']
                    ];

                    $saveMultiple = TransactionMultiplePayment::create($dataMultiple);
                    if (!$saveMultiple) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['fail to create transaction']
                        ];
                    }

                    return [
                        'status'   => 'success',
                        'type'     => 'no_topup',
                        'result'   => $dataCheck
                    ];
                }

                return [
                    'status'   => 'fail',
                    'messages' => ['transaction invalid']
                ];
            }
        } else {
            if (!is_null($idTrx)) {
                $dataTrx = Transaction::where('id_transaction', $idTrx)->with('outlet')->first();

                if (empty($dataTrx)) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['transaction not found']
                    ];
                }

                $dataBalance = [
                    'id_transaction'         => $dataTrx['id_transaction'],
                    'id_user'                => $dataTrx['id_user'],
                    'balance'                => -$dataTrx['balance_before'],
                    'id_reference'           => $dataTrx['id_transaction'],
                    'grand_total'            => $dataTrx['transaction_grandtotal'],
                    'trasaction_type'        => $dataTrx['trasaction_type'],
                    'transaction_grandtotal' => $dataTrx['transaction_grandtotal'],
                ];

                $dataPaymentBalance = [
                    'id_transaction'  => $idTrx,
                    'balance_nominal' => $data['balance_before']
                ];

                $savePaymentBalance = TransactionPaymentBalance::create($dataPaymentBalance);
                // return $savePaymentBalance;
                if (!$savePaymentBalance) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['fail to create transaction']
                    ];
                }

                $dataMultiple = [
                    'id_transaction' => $idTrx,
                    'type'           => 'Balance',
                    'id_payment'     => $savePaymentBalance['id_transaction_payment_balance']
                ];

                $saveMultiple = TransactionMultiplePayment::create($dataMultiple);
                if (!$saveMultiple) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['fail to create transaction']
                    ];
                }

                $balanceNotif = app($this->notif)->balanceNotif($dataTrx);
                if (!$balanceNotif) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['transaction not found']
                    ];
                }

                return [
                    'status'   => 'success',
                    'type'     => 'topup',
                    'result'   => $dataTrx
                ];
            }
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
