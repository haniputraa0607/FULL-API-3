<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentOffline;
use App\Jobs\FraudJob;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\LogTopupMidtrans;
use App\Http\Models\DealsPaymentMidtran;
use Modules\Subscription\Entities\SubscriptionPaymentMidtran;
use App\Http\Models\DealsUser;
use Modules\Subscription\Entities\SubscriptionUser;
use App\Http\Models\User;
use App\Http\Models\Outlet;
use App\Http\Models\LogBalance;
use App\Http\Models\LogTopup;
use App\Http\Models\UsersMembership;
use App\Http\Models\Setting;
use App\Http\Models\UserOutlet;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionShipment;
use App\Http\Models\LogPoint;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use Modules\Brand\Entities\Brand;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request as RequestGuzzle;
use Guzzle\Http\Message\Response as ResponseGuzzle;
use Guzzle\Http\Exception\ServerErrorResponseException;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use App\Lib\Midtrans;
use App\Lib\GoSend;
use Validator;
use Hash;
use DB;
use Mail;
use DateTime;

class ApiNotification extends Controller {

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->url_oauth  = env('URL_OUTLET_OAUTH');
        $this->oauth_id  = env('OUTLET_OAUTH_ID');
        $this->oauth_secret  = env('OUTLET_OAUTH_SECRET');
        $this->voucher  = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->promo_campaign	= "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
    }

    /* RECEIVE NOTIFICATION */
    public function receiveNotification(Request $request) {
        $midtrans = $request->json()->all();

        DB::beginTransaction();

        // CHECK ORDER ID
        if (stristr($midtrans['order_id'], "TRX")) {
            // TRANSACTION
            $transac = Transaction::with('user.memberships', 'logTopup')->where('transaction_receipt_number', $midtrans['order_id'])->first();

            if (empty($transac)) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Transaction not found']
                ]);
            }

            // PROCESS
            $checkPayment = $this->checkPayment($transac, $midtrans);
            if (!$checkPayment) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Transaction not found']
                ]);
            }

            // $sendNotif = $this->sendNotif($transac);
            // if (!$sendNotif) {
            //     return response()->json([
            //         'status'   => 'fail',
            //         'messages' => ['Transaction failed']
            //     ]);
            // }


            $newTrx = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('transaction_receipt_number', $midtrans['order_id'])->first();

            $checkType = TransactionMultiplePayment::where('id_transaction', $newTrx['id_transaction'])->get()->toArray();
            $column = array_column($checkType, 'type');

            // $user = User::where('id', $newTrx['id_user'])->first();
            // if (empty($user)) {
            //     DB::rollback();
            //     return response()->json([
            //         'status'   => 'fail',
            //         'messages' => ['Data Transaction Not Valid']
            //     ]);
            // }

            //booking GO-SEND
            if ($newTrx['trasaction_type'] == 'Pickup Order') {

                $newTrx['detail'] = TransactionPickup::with('transaction_pickup_go_send')->where('id_transaction', $newTrx['id_transaction'])->first();
                if ($newTrx['detail']) {
                    //inset pickup_at when pickup_type = right now
                    if($newTrx['detail']['pickup_type'] == 'right now'){
                        $settingTime = Setting::where('key', 'processing_time')->first();
                        if($settingTime && isset($settingTime['value'])){
                            $updatePickup = TransactionPickup::where('id_transaction', $newTrx['id_transaction'])->update(['pickup_at' => date('Y-m-d H:i:s', strtotime('+ '.$settingTime['value'].'minutes'))]);
                        }else{
                            $updatePickup = TransactionPickup::where('id_transaction', $newTrx['id_transaction'])->update(['pickup_at' => date('Y-m-d H:i:s')]);
                        }
                    }
                    // book gosend after outlet receive order only
                    // if ($newTrx['detail']['pickup_by'] == 'GO-SEND') {
                    //     $booking = $this->bookGoSend($newTrx);
                    //     if (isset($booking['status'])) {
                    //         return response()->json($booking);
                    //     }
                    // }
                } else {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Data Transaction Not Valid']
                    ]);
                }
            }

            if ($midtrans['status_code'] == 200) {
                // if (!in_array('Balance', $column)) {
                //     $savePoint = $this->savePoint($newTrx);
                //     if (!$savePoint) {
                //         DB::rollback();
                //         return response()->json([
                //             'status'   => 'fail',
                //             'messages' => ['Transaction failed']
                //         ]);
                //     }
                // }
                if($midtrans['transaction_status'] == 'settlement' && $midtrans['payment_type'] == 'credit_card'){}
                else{
                    if($midtrans['transaction_status'] == 'settlement'){
                        $notif = $this->notification($midtrans, $newTrx);
                        if (!$notif) {
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Transaction failed']
                            ]);
                        }
    
                        $sendNotifOutlet = app($this->trx)->outletNotif($newTrx['id_transaction']);
    
                        if($this->url_oauth != ''){
                            $kirim = $this->kirimOutlet($newTrx['transaction_receipt_number']);
                            if (isset($kirim['status']) && $kirim['status'] == 1) {
    
                                // // apply cashback to referrer
                                // \Modules\PromoCampaign\Lib\PromoCampaignTools::applyReferrerCashback($newTrx);
    
                                DB::commit();
                                // langsung
                                return response()->json(['status' => 'success']);
                            } elseif (isset($kirim['status']) && $kirim['status'] == 'fail') {
                                if (isset($kirim['messages'])) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => $kirim['messages']
                                    ]);
                                }
                            } else {
                                DB::rollback();
                                return response()->json([
                                    'status'   => 'fail',
                                    'messages' => ['failed']
                                ]);
                            }
                        }else{
                            //  // apply cashback to referrer
                            // \Modules\PromoCampaign\Lib\PromoCampaignTools::applyReferrerCashback($newTrx);
    
                            DB::commit();
                        }
                    }
                }

            } elseif ($midtrans['status_code'] == 201) {
                $notifPending = $this->notificationPending($midtrans, $newTrx);
                if (!$notifPending) {
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Transaction failed']
                    ]);
                }
            } elseif ($midtrans['status_code'] == 202) {
                if ($midtrans['transaction_status'] == 'deny') {
                    $notifDeny = $this->notificationDenied($midtrans, $newTrx);
                    if (!$notifDeny) {
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Transaction failed']
                        ]);
                    }
                } else {
                    $notifExpired = $this->notificationExpired($midtrans, $newTrx);
                    if (!$notifExpired) {
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Transaction failed']
                        ]);
                    }
                }
                if (count($checkType) > 0) {
                    foreach ($checkType as $key => $value) {
                        if ($value['type'] == 'Balance') {
                            $checkBalance = TransactionPaymentBalance::where('id_transaction_payment_balance', $value['id_payment'])->first();
                            if (!empty($checkBalance)) {
                                $insertDataLogCash = app($this->balance)->addLogBalance($newTrx['id_user'], $checkBalance['balance_nominal'], $newTrx['id_transaction'], 'Rejected Order', $newTrx['transaction_grandtotal']);
                                if (!$insertDataLogCash) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Cashback Failed']
                                    ]);
                                }
                                $usere = User::where('id', $newTrx['id_user'])->first();
                                $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $usere->phone,
                                    [
                                        "outlet_name"       => $newTrx['outlet']['outlet_name'],
                                        "transaction_date"  => $newTrx['transaction_date'],
                                        'id_transaction'    => $newTrx['id_transaction'],
                                        'receipt_number'    => $newTrx['transaction_receipt_number'],
                                        'received_point'    => (string) $checkBalance['balance_nominal']
                                    ]
                                );
                                if($send != true){
                                    DB::rollback();
                                    return response()->json([
                                            'status' => 'fail',
                                            'messages' => ['Failed Send notification to customer']
                                        ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }
        else if (stristr($midtrans['order_id'], "SUBS")) {
            // SUBSCRIPTION
            $subs = SubscriptionPaymentMidtran::where('order_id', $midtrans['order_id'])->first();

            if ($subs) {
                $checkSubsPayment = $this->checkSubsPayment($subs, $midtrans);

                if ($checkSubsPayment) {
                    DB::commit();
                    return response()->json(['status' => 'success']);
                }
            }
        }
        else {
            if (stristr($midtrans['order_id'], "TOP")) {
                //topup
                DB::beginTransaction();
                $checkLogMid = LogTopupMidtrans::where('order_id', $midtrans['order_id'])->first();
                if (empty($checkLogMid)) {
                    DB::rollback();
                    return response()->json(['status' => 'fail']);
                }

                $checkLog = LogTopup::where('id_log_topup', $checkLogMid['id_log_topup'])->first();
                if (empty($checkLog)) {
                    DB::rollback();
                    return response()->json(['status' => 'fail']);
                }

                $user = User::where('id', $checkLog['id_user'])->first();
                if (empty($user)) {
                    DB::rollback();
                    return response()->json(['status' => 'fail']);
                }

                $dataMid = $this->processMidtrans($midtrans);
                if (!$dataMid) {
                    DB::rollback();
                    return response()->json(['status' => 'fail']);
                }

                if (isset($dataMid['status_code']) && $dataMid['status_code'] == 200) {
                    if ($dataMid['transaction_status'] == 'capture' || $dataMid['transaction_status'] == 'settlement') {
                        $checkLog->topup_payment_status = 'Completed';
                        $checkLog->update();
                        if (!$checkLog) {
                            DB::rollback();
                            return response()->json(['status' => 'fail']);
                        }

                        $dataHash = [
                            'id_log_topup'          => $checkLog['id_log_topup'],
                            'id_user'               => $checkLog['id_user'],
                            'balance_before'        => $checkLog['balance_before'],
                            'nominal_bayar'         => $checkLog['nominal_bayar'],
                            'topup_value'           => $checkLog['topup_value'],
                            'balance_after'         => $checkLog['balance_after'],
                            'transaction_reference' => null,
                            'source'                => null,
                            'topup_payment_status'  => $checkLog['topup_payment_status'],
                            'payment_type'          => $checkLog['payment_type']
                        ];

                        $encodeCheck = json_encode($dataHash);
                        $enc = Hash::make($encodeCheck);

                        $checkLog->enc = $enc;
                        $checkLog->update();
                        if (!$checkLog) {
                            DB::rollback();
                            return response()->json(['status' => 'fail']);
                        }
                    }

                    $this->notifTopup($checkLog, $user, $dataMid);
                    return response()->json(['status' => 'success']);
                }
            } else {
                // DEALS
                $deals = DealsPaymentMidtran::where('order_id', $midtrans['order_id'])->first();

                if ($deals) {
                    $checkDealsPayment = $this->checkDealsPayment($deals, $midtrans);

                    if ($checkDealsPayment) {
                        DB::commit();
                        return response()->json(['status' => 'success']);
                    }
                }
            }

        }

        DB::rollback();
        return response()->json([
            'status'   => 'fail',
            'messages' => ['Transaction not found']
        ]);
    }

    public function notificationPending($mid, $trx)
    {
        $name    = $trx['user']['name'];
        $phone   = $trx['user']['phone'];
        $date    = $trx['transaction_date'];
        $outlet  = $trx['outlet']['outlet_name'];
        $receipt = $trx['transaction_receipt_number'];
        //$detail = $this->getHtml($trx, $trx['productTransaction'], $name, $phone, $date, $outlet, $receipt);
        $detail = $this->htmlDetailOrder($trx['id_transaction'], 'Pending');

        if ($trx['transaction_payment_status'] == 'Pending') {
            $title = 'Pending';
        }

        if ($trx['transaction_payment_status'] == 'Paid') {
            $title = 'Terbayar';
        }

        if ($trx['transaction_payment_status'] == 'Completed') {
            $title = 'Sukses';
        }

        if ($trx['transaction_payment_status'] == 'Cancelled') {
            $title = 'Gagal';
        }

        $payment = $this->getPayment($mid);

        $send = app($this->autocrm)->SendAutoCRM('Transaction Payment', $trx->user->phone, [
            'notif_type' => 'trx',
            'header_label' => $title,
            'date' => $trx['transaction_date'],
            'status' => $trx['transaction_payment_status'],
            'id_transaction' => $trx['id_transaction'],
            'name'  => $trx->user->name,
            'id' => $mid['order_id'],
            'outlet_name' => $outlet,
            'detail' => $detail,
            'payment' => $payment,
            'id_reference' => $mid['order_id'].','.$trx['id_outlet']
        ]);

        return $send;
    }

    public function notificationExpired($mid, $trx)
    {
        $name    = $trx['user']['name'];
        $phone   = $trx['user']['phone'];
        $date    = $trx['transaction_date'];
        $outlet  = $trx['outlet']['outlet_name'];
        $receipt = $trx['transaction_receipt_number'];
        //$detail = $this->getHtml($trx, $trx['productTransaction'], $name, $phone, $date, $outlet, $receipt);
        $detail = $this->htmlDetailOrder($trx['id_transaction'], 'Expired');

        if ($trx['transaction_payment_status'] == 'Pending') {
            $title = 'Pending';
        }

        if ($trx['transaction_payment_status'] == 'Paid') {
            $title = 'Terbayar';
        }

        if ($trx['transaction_payment_status'] == 'Completed') {
            $title = 'Sukses';
        }

        if ($trx['transaction_payment_status'] == 'Cancelled') {
            $title = 'Gagal';
        }

        $send = app($this->autocrm)->SendAutoCRM('Transaction Expired', $trx->user->phone, [
            'notif_type' => 'trx',
            'header_label' => $title,
            'id_transaction' => $trx['id_transaction'],
            'date' => $trx['transaction_date'],
            'status' => $trx['transaction_payment_status'],
            'name'  => $trx->user->name,
            'id' => $mid['order_id'],
            'outlet_name' => $outlet,
            'detail' => $detail,
            'id_reference' => $mid['order_id'].','.$trx['id_outlet']
        ]);

        return $send;
    }

    public function notificationDenied($mid, $trx)
    {
        $name    = $trx['user']['name'];
        $phone   = $trx['user']['phone'];
        $date    = $trx['transaction_date'];
        $outlet  = $trx['outlet']['outlet_name'];
        $receipt = $trx['transaction_receipt_number'];
        //$detail = $this->getHtml($trx, $trx['productTransaction'], $name, $phone, $date, $outlet, $receipt);
        $detail = $this->htmlDetailOrder($trx['id_transaction'], 'Denied');

        if ($trx['transaction_payment_status'] == 'Pending') {
            $title = 'Pending';
        }

        if ($trx['transaction_payment_status'] == 'Paid') {
            $title = 'Terbayar';
        }

        if ($trx['transaction_payment_status'] == 'Completed') {
            $title = 'Sukses';
        }

        if ($trx['transaction_payment_status'] == 'Cancelled') {
            $title = 'Gagal';
        }

        $send = app($this->autocrm)->SendAutoCRM('Transaction Failed', $trx->user->phone, [
            'notif_type' => 'trx',
            'header_label' => $title,
            'date' => $trx['transaction_date'],
            'id_transaction' => $trx['id_transaction'],
            'status' => $trx['transaction_payment_status'],
            'name'  => $trx->user->name,
            'id' => $mid['order_id'],
            'outlet_name' => $outlet,
            'detail' => $detail,
            'id_reference' => $mid['order_id'].','.$trx['id_outlet']
        ]);

        return $send;
    }

    public function notifTopup($data, $user, $mid)
    {
        $send = app($this->autocrm)->SendAutoCRM('Topup Success', $user->phone, ['notif_type' => 'topup', 'date' => $data['created_at'], 'status' => $data['transaction_payment_status'], 'name'  => $user->name, 'id' => $mid['order_id'], 'id_reference' => $mid['order_id']]);
    }

    function notification($mid, $trx)
    {
        $name    = $trx['user']['name'];
        $phone   = $trx['user']['phone'];
        $date    = $trx['transaction_date'];
        $outlet  = $trx['outlet']['outlet_name'];
        $receipt = $trx['transaction_receipt_number'];
        // $detail = $this->getHtml($trx, $trx['productTransaction'], $name, $phone, $date, $outlet, $receipt);
        $detail = $this->htmlDetailTrxSuccess($trx['id_transaction']);

        $title = 'Sukses';

        $send = app($this->autocrm)->SendAutoCRM('Transaction Success', $trx->user->phone, [
            'notif_type' => 'trx',
            'header_label' => $title,
            'id_transaction' => $trx['id_transaction'],
            'date' => $trx['transaction_date'],
            'status' => $trx['transaction_payment_status'],
            'name'  => $trx->user->name,
            'order_id' => $mid['order_id'],
            'outlet_name' => $outlet,
            'detail' => $detail,
            'id_reference' => $mid['order_id'].','.$trx['id_outlet']
        ]);

        return $send;
    }

    function savePoint($data)
    {
        if (!empty($data['user']['memberships'][0]['membership_name'])) {
            $level = $data['user']['memberships'][0]['membership_name'];
            $percentageP = $data['user']['memberships'][0]['benefit_point_multiplier'] / 100;
            $percentageB = $data['user']['memberships'][0]['benefit_cashback_multiplier'] / 100;
        } else {
            $level = null;
            $percentageP = 0;
            $percentageB = 0;
        }

        if ($data['transaction_point_earned'] != 0) {
            $settingPoint = Setting::where('key', 'point_conversion_value')->first();

            $dataLog = [
                'id_user'                     => $data['id_user'],
                'point'                       => $data['transaction_point_earned'],
                'id_reference'                => $data['id_transaction'],
                'source'                      => 'Transaction',
                'grand_total'                 => $data['transaction_grandtotal'],
                'point_conversion'            => $settingPoint['value'],
                'membership_level'            => $level,
                'membership_point_percentage' => $percentageP * 100
            ];

            $insertDataLog = LogPoint::updateOrCreate(['id_reference' => $data['id_transaction'], 'source' => 'Transaction'], $dataLog);
            if (!$insertDataLog) {
                DB::rollback();
                return false;
            }

            //update point user
            $totalPoint = LogPoint::where('id_user',$data['id_user'])->sum('point');
            $updateUserPoint = User::where('id', $data['id_user'])->update(['points' => $totalPoint]);
        }

        // apply cashback to referrer
        \Modules\PromoCampaign\Lib\PromoCampaignTools::applyReferrerCashback(Transaction::find($data['id_transaction']));

        if ($data['trasaction_payment_type'] != 'Balance') {
            if ($data['transaction_cashback_earned'] != 0) {

                $insertDataLogCash = app($this->balance)->addLogBalance( $data['id_user'], $data['transaction_cashback_earned'], $data['id_transaction'], 'Online Transaction', $data['transaction_grandtotal']);
                if (!$insertDataLogCash) {
                    DB::rollback();
                    return false;
                }
                $usere= User::where('id',$data['id_user'])->first();
                $send = app($this->autocrm)->SendAutoCRM('Transaction Point Achievement', $usere->phone,
                    [
                        "outlet_name"       => $data['outlet']['outlet_name'],
                        "transaction_date"  => $data['transaction_date'],
                        'id_transaction'    => $data['id_transaction'],
                        'receipt_number'    => $data['transaction_receipt_number'],
                        'received_point'    => (string) $data['transaction_cashback_earned']
                    ]
                );
                if($send != true){
                    DB::rollback();
                    return false;
                }
            }
        }

        $checkMembership = app($this->membership)->calculateMembership($data['user']['phone']);

        // DB::commit();
        return true;
    }

    function sendNotif($data)
    {
        if ($data['trasaction_type'] == 'Delivery') {
            $table = 'transaction_shipments';
            $field = 'delivery';
        } else {
            $table = 'transaction_pickups';
            $field = 'pickup_order';
        }

        $detail = DB::table($table)->where('id_transaction', $data['id_transaction'])->first();
        $link = MyHelper::get(env('SHORT_LINK_URL').'/?key='.env('SHORT_LINK_KEY').'&url='.$detail->short_link);

        if (isset($link['error']) && $link['error'] == 0) {
            $admin = UserOutlet::with('outlet')->where('id_outlet', $data['id_outlet'])->where($field, 1)->get()->toArray();

            foreach ($admin as $key => $value) {
                $send = app($this->autocrm)->SendAutoCRM('Admin Notification', $value['phone'], [
                    'title'  => "
Outlet Name : ".$value['outlet']['outlet_name']."
Order ID: ".$detail['order_id']."
Tanggal: ".$data['transaction_date']."
Detail: ".$link['short'],
                ]);

                if (!$send) {
                    return false;
                }
            }
        }

        return true;
    }

    /* CHECK PAYMENT */
    function checkPayment($trx, $midtrans) {
        if (isset($trx['logTopup'])) {
            $mid = $this->processMidtrans($midtrans);

            $mid['id_log_topup'] = $trx['logTopup']['id_log_topup'];

            $saMid = LogTopupMidtrans::create($mid);
            if (!$saMid) {
                return false;
            }

            if (isset($mid['status_code']) && $mid['status_code'] == 200) {
                if ($mid['transaction_status'] == 'capture' || $mid['transaction_status'] == 'settlement') {
                    $check = LogTopup::where('id_log_topup', $trx['logTopup']['id_log_topup'])->update(['topup_payment_status' => 'Completed', 'payment_type' => 'Midtrans']);

                    if ($check) {
                        $upTrx = Transaction::where('id_transaction', $trx['id_transaction'])->update(['transaction_payment_status' => 'Completed']);
                        if (!$upTrx) {
                            return false;
                        }

                        $fraud = $this->checkFraud($trx);
                        if ($fraud == false) {
                            return false;
                        }


                        return app($this->balance)->addTopupToBalance($trx['logTopup']['id_log_topup']);
                    }
                } else {
                    $check = LogTopup::where('id_log_topup', $trx['logTopup']['id_log_topup'])->update(['topup_payment_status' => ucwords($mid['transaction_status']), 'payment_type' => 'Midtrans']);
                }

                return false;
            }

            return false;
        } else {
            $check = TransactionPaymentMidtran::where('order_id', $midtrans['order_id'])->where('id_transaction', $trx->id_transaction)->get()->first();
            if (!$check) {
                return false;
            }

            $save = $this->paymentMidtrans($trx, $midtrans);
            if (!$save) {
                return false;
            }

            return true;
        }
    }

    /* CHECNK DEALS PAYMENT */
    function checkDealsPayment($deals, $midtrans) {
        DB::beginTransaction();
        $midtrans = $this->processMidtrans($midtrans);
        unset($midtrans['store']);

        // UPDATE
        $update = DealsPaymentMidtran::where('order_id', $midtrans['order_id'])->update($midtrans);

        if ($update) {
            $statusPay = "Pending";
            if (isset($midtrans['status_code']) && $midtrans['status_code'] == 200) {
                // if($midtrans['transaction_status'] != 'settlement' && $midtrans['payment_type'] != 'credit_card'){
                    $deals = DealsUser::with(['userMid', 'deals'])->where('id_deals_user', $deals->id_deals_user)->first();

                    $title = "";
                    if(isset($deals['deals']['deals_title']) && $deals['deals']['deals_title'] != null){
                        $title = $deals['deals']['deals_title'];
                    }

                    if(isset($deals['deals']['deals_second_title']) && $deals['deals']['deals_second_title']){
                        $title = $title.' '.$deals['deals']['deals_second_title'];
                    }
                    // dd($deals);
                    $send = app($this->autocrm)->SendAutoCRM(
                        'Payment Deals Success',
                        $deals['userMid']['phone'],
                        [
                            'deals_title'       => $title,
                            'id_deals_user'     => $deals['id_deals_user']
                        ]
                    );
                // }
                $statusPay = 'Completed';
            }elseif (isset($midtrans['status_code']) && $midtrans['status_code'] == 201){
                $statusPay = 'Pending';
            }elseif (isset($midtrans['status_code']) && $midtrans['status_code'] == 202) {
                 $statusPay = 'Cancelled';
            }
            // UPDATE STATUS PEMBAYARAN
            $updatePembayaran = DealsUser::where('id_deals_user', $deals->id_deals_user)->update(['paid_status' => $statusPay]);
            // dd($updatePembayaran);
            if ($updatePembayaran) {
                DB::commit();
                return true;
            }
        }
        DB::rollback();
        return false;
    }

    /* CHECK SUBSCRIPTION PAYMENT */
    function checkSubsPayment($subs, $midtrans) {
        DB::beginTransaction();
        $midtrans = $this->processMidtrans($midtrans);
        // UPDATE
        unset($midtrans['store']);
        // return $midtrans;
        $update = SubscriptionPaymentMidtran::where('order_id', $midtrans['order_id'])->update($midtrans);

        if ($update) {
            $statusPay = "Pending";
            if (isset($midtrans['status_code']) && $midtrans['status_code'] == 200) {
                // if($midtrans['transaction_status'] != 'settlement' && $midtrans['payment_type'] != 'credit_card'){
                    $subs = SubscriptionUser::with(['user', 'subscription'])->where('id_subscription_user', $subs->id_subscription_user)->first();

                    $title = "";
                    if( $subs['subscription']['subscription_title'] ?? false){
                        $title = $subs['subscription']['subscription_title'];
                    }

                    if( $subs['subscription']['subscription_sub_title'] ?? false){
                        $title = $title.' '.$subs['subs']['subscription_sub_title'];
                    }
                    // dd($deals);
                    $send = app($this->autocrm)->SendAutoCRM(
                        'Payment Subscription Success',
                        $subs['user']['phone'],
                        [
                            'subscription_title'       => $title,
                            'id_subscription_user'     => $subs['id_subscription_user']
                        ]
                    );
                // }
                $statusPay = 'Completed';
            }elseif (isset($midtrans['status_code']) && $midtrans['status_code'] == 201){
                $statusPay = 'Pending';
            }elseif (isset($midtrans['status_code']) && $midtrans['status_code'] == 202) {
                 $statusPay = 'Cancelled';
            }
            // UPDATE STATUS PEMBAYARAN
            $updatePembayaran = SubscriptionUser::where('id_subscription_user', $subs->id_subscription_user)->update(['paid_status' => $statusPay]);

            if ($updatePembayaran) {
                DB::commit();
                return true;
            }
        }
        DB::rollback();
        return false;
    }

    /* DATA MIDTRANS */
    function processMidtrans($midtrans) {
        $transaction_time   = isset($midtrans['transaction_time']) ? $midtrans['transaction_time'] : null;
        $transaction_status = isset($midtrans['transaction_status']) ? $midtrans['transaction_status'] : null;
        $transaction_id     = isset($midtrans['transaction_id']) ? $midtrans['transaction_id'] : null;
        $status_message     = isset($midtrans['status_message']) ? $midtrans['status_message'] : null;
        $status_code        = isset($midtrans['status_code']) ? $midtrans['status_code'] : null;
        $signature_key      = isset($midtrans['signature_key']) ? $midtrans['signature_key'] : null;
        $payment_type       = isset($midtrans['payment_type']) ? $midtrans['payment_type'] : null;
        $order_id           = isset($midtrans['order_id']) ? $midtrans['order_id'] : null;
        $masked_card        = isset($midtrans['masked_card']) ? $midtrans['masked_card'] : null;
        $gross_amount       = isset($midtrans['gross_amount']) ? $midtrans['gross_amount'] : null;
        $fraud_status       = isset($midtrans['fraud_status']) ? $midtrans['fraud_status'] : null;
        $approval_code      = isset($midtrans['approval_code']) ? $midtrans['approval_code'] : null;
        $bank = null;
        $store = null;

        if (isset($midtrans['permata_va_number'])) {
            $eci  = isset($midtrans['permata_va_number']) ? $midtrans['permata_va_number'] : null;
            $bank = 'Permata';
        } elseif (isset($midtrans['bill_key'])) {
            $eci = $midtrans['biller_code'].$midtrans['bill_key'];
            $bank = 'Mandiri';
        } elseif (isset($midtrans['payment_code'])) {
            $eci = $midtrans['payment_code'];
            $store = $midtrans['store'];
        } else {
            $bank = isset($midtrans['va_numbers'][0]['bank']) ? $midtrans['va_numbers'][0]['bank'] : null;
            $eci  = isset($midtrans['va_numbers'][0]['va_number']) ? $midtrans['va_numbers'][0]['va_number'] : null;
        }

        $data = [
            'masked_card'        => $masked_card,
            'approval_code'      => $approval_code,
            'bank'               => $bank,
            'eci'                => $eci,
            'store'              => $store,
            'transaction_time'   => $transaction_time,
            'gross_amount'       => $gross_amount,
            'order_id'           => $order_id,
            'payment_type'       => ucwords(str_replace('_', ' ', $payment_type)),
            'signature_key'      => $signature_key,
            'status_code'        => $status_code,
            'vt_transaction_id'  => $transaction_id,
            'transaction_status' => $transaction_status,
            'fraud_status'       => $fraud_status,
            'status_message'     => $status_message
        ];

        return $data;
    }

    /* CHECK PAYMENT MIDTRANS */
    function paymentMidtrans($trx, $midtrans) {
        $data = $this->processMidtrans($midtrans);

        // UPDATE
        $update = TransactionPaymentMidtran::where('id_transaction', $trx->id_transaction)->where('order_id', $midtrans['order_id'])->update($data);
        if (!$update) {
            return false;
        }

        if (isset($midtrans['status_code']) && $midtrans['status_code'] == 200) {
            if ($midtrans['transaction_status'] == 'capture' || $midtrans['transaction_status'] == 'settlement') {
                $check = Transaction::where('id_transaction', $trx->id_transaction)->update(['transaction_payment_status' => 'Completed']);
                if (!$check) {
                    return false;
                }

                $fraud = $this->checkFraud($trx);
                if (!$fraud) {
                    return false;
                }
            } else {
                $check = Transaction::where('id_transaction', $trx->id_transaction)->update(['transaction_payment_status' => ucwords($midtrans['transaction_status'])]);

                if (!$check) {
                    return false;
                }
            }
        } elseif (isset($midtrans['status_code']) && $midtrans['status_code'] == 202) {
            $check = Transaction::where('id_transaction', $trx->id_transaction)->update(['transaction_payment_status' => 'Cancelled']);

            if (!$check) {
                return false;
            }

            if ($trx->id_promo_campaign_promo_code) {
            	$update_promo_report = app($this->promo_campaign)->deleteReport($trx->id_transaction, $trx->id_promo_campaign_promo_code);
            	if (!$update_promo_report) {
            		return false;
	            }

            }

            $update_voucher = app($this->voucher)->returnVoucher($trx->id_transaction);
            if (!$update_voucher) {
            	return false;
            }
        }

        return true;
    }

    /* CHECK PAYMENT BALANCE */
    function paymentBalance($trx, $midtrans) {
        $data = $this->processMidtrans($midtrans);

        $topup = LogTopup::where('transaction_reference', $trx->id_transaction)->first();

        if ($topup) {
            $updateTopMid = LogTopupMidtrans::where('order_id', $midtrans['order_id'])->where('id_log_topup', $topup->id_log_topup)->update($data);

            if ($updateTopMid) {
                // update
                $updateTopup = LogTopup::where('id_log_topup', $topup->id_log_topup)->update(['topup_payment_status' => 'Completed']);

                if ($updateTopup) {
                    return app($this->balance)->addTopupToBalance($topup->id_log_topup);
                }
            }
        }

        return false;
    }

    public function adminOutletNotification($receipt) {
        $transaction = Transaction::where('transaction_receipt_number', $receipt)->first();
        // return $transaction;
        if (!$transaction) {
            return ['status' => 'fail'];
        }

        $type = strtolower(str_replace(' ', '_', $transaction['trasaction_type']));

        $admin = UserOutlet::where(['id_outlet' => $transaction['id_outlet'], $type => 1])->get()->toArray();

        if (!$admin) {
            return ['status' => 'fail'];
        }

        foreach ($admin as $key => $value) {

        }
    }

    public function adminOutlet(Request $request)
    {
        $post = $request->json()->all();
        $transaction = Transaction::with('outlet', 'user', 'products')->where('transaction_receipt_number', $post['receipt'])->first();
        if (!$transaction) {
            return ['status' => 'fail', 'messages' => ['Transaction Not Found']];
        }

        if ($transaction['trasaction_type'] == 'Delivery') {
            $transaction['detail'] = TransactionShipment::with('admin_receive', 'admin_taken')->where('id_transaction', $transaction['id_transaction'])->first();
        } else {
            $transaction['detail'] = TransactionPickup::with('admin_receive', 'admin_taken')->where('id_transaction', $transaction['id_transaction'])->first();
        }

        $admin = UserOutlet::where('phone', $post['phone'])->first();

        if (!$admin) {
            return ['status' => 'fail', 'messages' => ['Admin Not Found']];
        }

        if ($admin[strtolower(str_replace(' ', '_', $transaction['trasaction_type']))] != 1) {
            return ['status' => 'fail', 'messages' => ['Access Transaction Denied For This Admin']];
        }

        return response()->json([
            'status' => 'success',
            'trx' => $transaction,
            'admin' => $admin
        ]);
    }

    public function adminOutletComfirm(Request $request) {
        $post = $request->json()->all();
        // return $post;
        $transaction = Transaction::where('transaction_receipt_number', $post['receipt'])->first();
        if (empty($transaction)) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Transaction Not Found']
            ]);
        }

        if ($post['type'] == 'delivery') {
            $detail = TransactionShipment::where('id_transaction', $transaction['id_transaction'])->first();
        } else {
            $detail = TransactionPickup::where('id_transaction', $transaction['id_transaction'])->first();
        }
        if (empty($detail)) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Transaction Not Found']
            ]);
        }

        if ($post['status'] == 'receive') {
            $detail->receive_at = date('Y-m-d H:i:s');
            $detail->id_admin_outlet_receive = $post['id'];
        } else {
            if ($post['type'] == 'delivery') {
                $detail->send_at = date('Y-m-d H:i:s');
                $detail->id_admin_outlet_send = $post['id'];
            } else {
                $detail->taken_at = date('Y-m-d H:i:s');
                $detail->id_admin_outlet_taken = $post['id'];
                $transaction->show_rate_popup = 1;
                $transaction->save();
            }
        }

        $detail->save();

        if (!$detail) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Transaction Not Found']
            ]);
        }


        return response()->json([
            'status' => 'success'
        ]);
    }

    function balanceNotif($data) {
        $user = User::with('memberships')->where('id', $data['id_user'])->first();

        if (!empty($user['memberships'][0]['membership_name'])) {
            $level = $user['memberships'][0]['membership_name'];
            $percentageP = $user['memberships'][0]['benefit_point_multiplier'] / 100;
            $percentageB = $user['memberships'][0]['benefit_cashback_multiplier'] / 100;
        } else {
            $level = null;
            $percentageP = 0;
            $percentageB = 0;
        }

        $trxBalance = TransactionMultiplePayment::where('id_transaction', $data['id_transaction'])->first();

        if (empty($trxBalance)) {
            $insertDataLogCash = app($this->balance)->addLogBalance( $data['id_user'], -$data['transaction_grandtotal'], $data['id_transaction'], 'Online Transaction', $data['transaction_grandtotal']);
        } else {
            $paymentBalanceTrx = TransactionPaymentBalance::where('id_transaction', $data['id_transaction'])->first();
            $insertDataLogCash = app($this->balance)->addLogBalance( $data['id_user'], -$paymentBalanceTrx['balance_nominal'], $data['id_transaction'], 'Online Transaction', $data['transaction_grandtotal']);
        }

        if ($insertDataLogCash == false) {
            return false;
        }

        return true;
    }

    function checkFraud($trx){

        $userData = User::find($trx['id_user']);

        //update count transaction
        $updateCountTrx = User::where('id', $userData['id'])->update([
            'count_transaction_day' => $userData['count_transaction_day'] + 1,
            'count_transaction_week' => $userData['count_transaction_week'] + 1,
        ]);

        if (!$updateCountTrx) {
            DB::rollBack();
            return false;
        }

        $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->first()->is_active;

        if($config_fraud_use_queue == 1){
            FraudJob::dispatch($userData, $trx, 'transaction')->onConnection('fraudqueue');
        }else {
            $checkFraud = app($this->setting_fraud)->checkFraudTrxOnline($userData, $trx);
        }

        return true;
    }

    function bookGoSend($trx){
        //create booking GO-SEND
        $origin['name']             = $trx['detail']['transaction_pickup_go_send']['origin_name'];
        $origin['phone']            = $trx['detail']['transaction_pickup_go_send']['origin_phone'];
        $origin['latitude']         = $trx['detail']['transaction_pickup_go_send']['origin_latitude'];
        $origin['longitude']        = $trx['detail']['transaction_pickup_go_send']['origin_longitude'];
        $origin['address']          = $trx['detail']['transaction_pickup_go_send']['origin_address'];
        $origin['note']             = $trx['detail']['transaction_pickup_go_send']['origin_note'];

        $destination['name']        = $trx['detail']['transaction_pickup_go_send']['destination_name'];
        $destination['phone']       = $trx['detail']['transaction_pickup_go_send']['destination_phone'];
        $destination['latitude']    = $trx['detail']['transaction_pickup_go_send']['destination_latitude'];
        $destination['longitude']   = $trx['detail']['transaction_pickup_go_send']['destination_longitude'];
        $destination['address']     = $trx['detail']['transaction_pickup_go_send']['destination_address'];
        $destination['note']        = $trx['detail']['transaction_pickup_go_send']['destination_note'];

        $packageDetail = Setting::where('key', 'go_send_package_detail')->first();
        if($packageDetail){
            $packageDetail = str_replace('%order_id%', $trx['detail']['order_id'], $packageDetail['value']);
        }else{
            $packageDetail = "Order ".$trx['detail']['order_id'];
        }

        $booking = GoSend::booking($origin, $destination, $packageDetail, $trx['transaction_receipt_number']);
        if(isset($booking['status']) && $booking['status'] == 'fail'){
            return $booking;
        }

        if(!isset($booking['id'])){
            return ['status' => 'fail', 'messages' => $booking['messages']??['failed booking GO-SEND']];
        }
        //update id from go-send
        $updateGoSend = TransactionPickupGoSend::find($trx['detail']['transaction_pickup_go_send']['id_transaction_pickup_go_send']);
        $status = GoSend::getStatus($trx['transaction_receipt_number']);
        if($updateGoSend){
            $updateGoSend->go_send_id = $booking['id'];
            $updateGoSend->go_send_order_no = $booking['orderNo'];
            $updateGoSend->latest_status = $status['status']??null;
            $updateGoSend->save();

            if(!$updateGoSend){
                return ['status' => 'fail', 'messages' => ['failed update Transaction GO-SEND']];
            }
        }
    }

    public function getPayment($mid)
    {
        $label_place = 'Bank';
        $number = "";
        $bank = "";
        if (isset($mid['permata_va_number'])) {
            $number = $mid['permata_va_number'];
            $bank = 'Permata';
        } elseif (isset($mid['biller_code'])) {
            $number = $mid['bill_key'];
            $bank = 'Mandiri';
        } elseif (isset($mid['payment_code'])) {
            $number = $mid['payment_code'];
            $bank = $mid['store'];
            $label_place = 'Store';
        } else {
            $number = isset($mid['va_numbers'][0]['va_number']) ? $mid['va_numbers'][0]['va_number'] : null;
            $bank = isset($mid['va_numbers'][0]['bank']) ? strtoupper($mid['va_numbers'][0]['bank']) : null;
        }

        $kode = '';

        if ($bank == 'Mandiri') {
            $kode = "Kode : ".$mid['biller_code'];
        }

        $type   = ucwords(str_replace('_', ' ', $mid['payment_type']));

        return '<table style="padding:0;margin-bottom: -50px;" width="800" cellspacing="0" cellpadding="0" border="0">
 <tbody>
    <tr>
       <td style="color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" bgcolor="#ffffff">
          <table style="margin:0;padding:0" width="100%" align="left">
             <tbody>
              <tr>
                <td style="background:#ffffff;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:5px 10px;width: 100px" valign="top" bgcolor="#FFFFFF" align="left">
                   <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.$label_place.' </span>
                </td>
                <td colspan="3" style="background:#ffffff;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:5px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                    <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">:  <b>'.$bank.'</b> </span>
                </td>
             </tr>
             <tr>
                <td style="background:#ffffff;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:5px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                   <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Virtual Number</span>
                </td>
                <td colspan="3" style="background:#ffffff;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:5px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                    <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">:  <b>'.$number.' '.$kode.' </span>
                </td>
             </tr>
             <tr>
                <td style="background:#ffffff;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:5px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                   <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Metode</span>
                </td>
                <td colspan="3" style="background:#ffffff;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:5px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                    <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">:  <b>'.$type.' </span>
                </td>
             </tr>
             </tbody>
          </table>
       </td>
    </tr>
 </tbody>
</table>';
    }

    function getHtml($trx, $item, $name, $phone, $date, $outlet, $receipt)
    {
        $dataItem = '';
        foreach ($item as $key => $value) {
            $dataItem .= '<tr>
                        <td style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:10px 10px" width="25%" valign="middle" bgcolor="#FFFFFF" align="left">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.$value['product']['product_name'].'</span>
                        </td>
                        <td style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:10px 10px" width="25%" valign="middle" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">IDR</span> <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.number_format($value['transaction_product_price']).'</span>
                        </td>
                        <td style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:10px 10px" width="10%" valign="middle" bgcolor="#FFFFFF" align="center">
                          '.$value['transaction_product_qty'].'
                        </td>
                        <td style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:10px 10px" width="25%" valign="middle" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">IDR</span> <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.number_format($value['transaction_product_subtotal']).'</span>
                        </td>
                     </tr>';
        }

        $setting = Setting::where('key', 'transaction_grand_total_order')->first();
        $order = $setting['value'];

        $exp   = explode(',', $order);
        $manna = [];

        for ($i=0; $i < count($exp); $i++) {
            if (substr($exp[$i], 0, 5) == 'empty') {
                unset($exp[$i]);
                continue;
            }

            if ($exp[$i] == 'subtotal') {
                $manna[$exp[$i]] = $trx['transaction_subtotal'];
            }

            if ($exp[$i] == 'tax') {
                $manna[$exp[$i]] = $trx['transaction_tax'];
            }

            if ($exp[$i] == 'discount') {
                $manna[$exp[$i]] = $trx['transaction_discount'];
            }

            if ($exp[$i] == 'service') {
                $manna[$exp[$i]] = $trx['transaction_service'];
            }

            if ($exp[$i] == 'shipping') {
                $manna[$exp[$i]] = $trx['transaction_shipment'];
            }
        }

        $dataOrder = '';

        foreach ($manna as $row => $m) {
            if ($m != 0) {
                $dataOrder .= '<tr style="text-align:right">
                        <td colspan="3" style="background:#ffffff;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.ucwords($row).'</span>
                        </td>
                        <td style="background:#ffffff;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">IDR</span> <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.number_format($m).'</span>
                        </td>
                    </tr>';
            }
        }

        return '<table style="border-collapse:collapse;border-spacing:0;margin:0 auto;padding:0" width="800" cellspacing="0" cellpadding="0" border="0" align="center">
               <tbody>
                  <tr>
                     <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" align="left">
                        <table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%">
                           <tbody>
                           </tbody>
                        </table>
                     </td>
                  </tr>
                  <tr>
                     <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" bgcolor="#ffffff">
                        <table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%" align="left">
                           <tbody>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="20"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="550" height="20"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="20"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="550" align="left"><table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%" align="left">
   <tbody>
      <tr>
         <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
            <!-- <div style="margin:5px 2px">
               <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Dear <b>Ivan Kurniawan Prasetyo</b>,</p>
            </div> -->
            <div style="margin:5px 2px">
               <!-- <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Terima kasih telah memesan layanan delivery di <b>Hakatta Ikkousha Demangan</b> melalui <span class="il">Vourest</span>.com. Pesanan Anda sudah kami teruskan ke <b>Hakatta Ikkousha Demangan</b> untuk ditindak lanjuti.</p> -->
               <!-- <br> -->
               <!-- <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Status Pesanan Anda :</p> -->
              <!--  <div style="background:#f4f4f4;border:1px solid #e0e0e0;color:#808080;font-family:Source Sans Pro;font-size:14px;margin:10px auto;padding-bottom:20px;padding-top:20px;text-align:center;width:50%" align="center">
                  <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">
                     <strong style="color:#555;font-size:14px">
                     Pending
                     </strong>
                  </p>
               </div> -->
               <!-- <br> -->
               <!-- <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0 0 8px">Detail pesanan Anda:</p>
               <br> -->
               <table style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;font-size:12px;margin:0 0 25px;padding:0" width="100%" cellspacing="0" cellpadding="5" border="0" bgcolor="#FFFFFF">
                  <tbody>
                     <tr>
                        <th colspan="4" style="background:#6C5648;border-bottom-style:none;color:#ffffff;padding-left:10px;padding-right:10px" bgcolor="background: rgb(40, 141, 73)">
                           <h2 style="color:#ffffff;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:5px 0">Customer</h2>
                        </th>
                     </tr>
                     <tr>
                        <td style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Nama </span>
                        </td>
                        <td colspan="3" style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                            <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">:  '.$name.' </span>
                        </td>
                     </tr>
                     <tr>
                        <td style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Kontak</span>
                        </td>
                        <td colspan="3" style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="left">
                            <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">:  '.$phone.' </span>
                        </td>
                     </tr>

                    <tr>
                        <th colspan="4" style="background:#6C5648;border-bottom-style:none;color:#ffffff;padding-left:10px;padding-right:10px" bgcolor="background: rgb(40, 141, 73)">
                           <h2 style="color:#ffffff;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:5px 0"> #<a style="color:#ffffff!important;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://vourest.com/history/transaction/hakaikykdm/1011&amp;source=gmail&amp;ust=1539830594941000&amp;usg=AFQjCNG9sneH2MymFvLJsuVjeOY2XvH7QA">'.$receipt.'</a></h2>
                        </th>
                    </tr>
                    <tr>
                        <td colspan="4" style="background:#ffffff;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">'.$date.'</span>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4" style="background:#6C5648;border-bottom-style:none;color:#ffffff;padding-left:10px;padding-right:10px" bgcolor="background: rgb(40, 141, 73)">
                        </th>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:11px;line-height:1.5;margin:0;padding:15px 10px" width="25%" valign="top" bgcolor="#F0F0F0" align="center">
                           <strong style="color:#555;font-size:14px">Nama</strong>
                        </td>
                        <td style="background:#f0f0f0;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:11px;line-height:1.5;margin:0;padding:15px 10px" width="25%" valign="top" bgcolor="#F0F0F0" align="right">
                           <strong style="color:#555;font-size:14px">Harga</strong>
                        </td>
                        <td style="background:#f0f0f0;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:11px;line-height:1.5;margin:0;padding:15px 10px" width="10%" valign="top" bgcolor="#F0F0F0" align="center">
                           <strong style="color:#555;font-size:14px">Jumlah</strong>
                        </td>
                        <td style="background:#f0f0f0;border-bottom-color:#cccccc;border-bottom-style:solid;border-bottom-width:1px;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:11px;line-height:1.5;margin:0;padding:15px 10px" width="10%" valign="top" bgcolor="#F0F0F0" align="center">
                           <strong style="color:#555;font-size:14px">Subtotal</strong>
                        </td>
                     </tr>


                    '.$dataItem.'


                    '.$dataOrder.'
                    <tr style="text-align:right">
                        <td colspan="3" style="background:#ffffff;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0"><b>Grand Total</b></span>
                        </td>
                        <td style="background:#ffffff;border-collapse:collapse;border-spacing:0;color:#555;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:15px 10px" valign="top" bgcolor="#FFFFFF" align="right">
                           <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:15px;line-height:1.5;margin:0;padding:0"><b>IDR</b></span> <span style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:15px;line-height:1.5;margin:0;padding:0"><b>'.number_format($trx['transaction_grandtotal']).'</b></span>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4" style="background:#6C5648;border-bottom-style:none;color:#ffffff;padding-left:10px;padding-right:10px" bgcolor="background: rgb(40, 141, 73)">
                        </th>
                    </tr>
                  </tbody>
               </table>

               <!-- <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">Jika ada permasalahan terkait pesanan ini, silakan <span style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0;text-decoration:none">Hubungi <span class="m_6657055476784441913il"><b>Hakatta Ikkousha Demangan</b>, (0274) 557651</span></span></p> -->
               <!-- <br> -->
               <!-- <div style="background:#f4f4f4;padding-bottom:20px;padding-top:20px">
               <a href="http://vourest.com/history/transaction/hakaikykdm/1011" style="background:#6C5648;clear:both;color:#ffffff!important;display:block;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0 auto;padding:10px 0;text-align:center;text-decoration:none!important;width:50%" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://vourest.com/history/transaction/hakaikykdm/1011&amp;source=gmail&amp;ust=1539830594941000&amp;usg=AFQjCNG9sneH2MymFvLJsuVjeOY2XvH7QA">Lihat <span class="il">Transaksi</span></a>
               </div> -->
            </div>
         </td>
      </tr>
   </tbody>
</table>
<table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%">
   <tbody>
      <tr>
         <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="50"></td>
      </tr>
      <tr>
         <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
            <p style="color:#555;font-family:\'Source Sans Pro\',sans-serif;font-size:14px;line-height:1.5;margin:0;padding:0">
               Terima kasih atas perhatian dan kepercayaan Anda.
            </p>
         </td>
      </tr>
   </tbody>
</table>
</td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="30"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="550" height="30"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="30"></td>
                              </tr>
                           </tbody>
                        </table>
                     </td>
                  </tr>
                  <tr>
                     <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                        <table style="background:#f0f0f0;border-collapse:collapse;border-spacing:0;font-size:12px;margin:0;padding:0" width="100%" bgcolor="#f0f0f0" align="left">
                           <tbody>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="24" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="10" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="516" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="5"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="24" height="26">
                                    <img class="m_6657055476784441913CToWUd CToWUd" alt="Hati-hati" src="https://ci4.googleusercontent.com/proxy/17QqMspfedBHa9ObiGH2rhbjYiGN_eclyCwL-Ws0XG_XSoZfj3vqh6hF2USepehm1Xc7TX788N1xbTEq_KlBHisQN_BSgbs=s0-d-e1-ft#https://www.Vourest.com/images/icon_warning.png" style="border:0 none;min-height:auto;line-height:100%;outline:none;text-decoration:none">
                                 </td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="10"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="516" align="left">
                                    <p style="color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                       Harap tidak menginformasikan
                                       <b>
                                       nomor kontak, alamat e-mail, atau password
                                       </b>
                                       Anda kepada siapapun, termasuk pihak yang mengatasnamakan <span class="m_6657055476784441913il"><span class="il">'.$outlet.'</span></span>
                                    </p>
                                 </td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="24" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="10" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="516" height="5"></td>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" width="15" height="5"></td>
                              </tr>
                           </tbody>
                        </table>
                     </td>
                  </tr>
                  <tr>
                     <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                        <table style="border-collapse:collapse;border-spacing:0;margin:0;padding:0" width="100%">
                           <tbody>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="30"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                    <table style="border-collapse:collapse;border-spacing:0;font-size:11px;line-height:1.7;margin:0;padding:0;text-align:left" width="300" align="left">
                                       <tbody>
                                          <tr>
                                             <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                                <p style="color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                                   Copyright © 2018 CV. <span class="m_6657055476784441913il"><span class="il">Behave</span></span>. All Rights Reserved
                                                </p>
                                                <p style="color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                                   <!-- <a href="http://vourest.com" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://vourest.com&amp;source=gmail&amp;ust=1539830594941000&amp;usg=AFQjCNFJwntmskFeBJH_beiKC_Ae0R1yTA">http://<span class="il">behave</span>.com</a> -->
                                                </p>
                                             </td>
                                          </tr>
                                          <tr>
                                             <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="15"></td>
                                          </tr>
                                       </tbody>
                                    </table>
                                    <table style="border-collapse:collapse;border-spacing:0;font-size:11px;line-height:1.7;margin:0;padding:0;text-align:right" width="250" align="right">
                                       <tbody>
                                          <tr>
                                             <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                                <ul style="list-style:none;margin:0;padding:0">
                                                   <li style="display:inline-block">
                                                      <a href="https://play.google.com/store/apps/details?id=com.android.android" style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://play.google.com/store/apps/details?id%3Dcom.android.android&amp;source=gmail&amp;ust=1539830594941000&amp;usg=AFQjCNFpXjwAMbT7tvIMkM-mnQ-dIPjy6A"><img class="m_6657055476784441913CToWUd CToWUd" alt="Download Aplikasi Android" style="border:0 none;min-height:33px;line-height:100%;outline:none;text-decoration:none;width:100px" src="https://ci5.googleusercontent.com/proxy/iZ-k_3xY6K6Zp6xYJ1gXvbiA2V9W1JnO5-hJ2E50_uw2q7jvN3mP-REIv2yKoCcBc1b8ERsAYegRzp7sIWecJDiTD1Mhe13mpmW4PfRP3tbAEHjIUQrUzDNY7NM9zg=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/downloadAndroid.png">
                                                      </a>
                                                   </li>
                                                   <li style="display:inline-block">
                                                      <a href="http://itunes.apple.com/behave" style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://itunes.apple.com/vourest&amp;source=gmail&amp;ust=1539830594941000&amp;usg=AFQjCNEd9ZLBQ_ftzEhqaljekbj6Q8hNrA"><img class="m_6657055476784441913CToWUd CToWUd" alt="Download Aplikasi iOS" style="border:0 none;min-height:33px;line-height:100%;outline:none;text-decoration:none;width:100px" src="https://ci6.googleusercontent.com/proxy/o3ZDhqansaD0LIRLGxtQZ58em0ptMR3jvVRCJGi8mCrT7g3hVbFfKC2ji-6jrRWPwDLOv3srnLGKAyh1Qp6nePqRBsbELeFmTZjh_0PRamqndU9xRWZYXf7z=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/downloadIos.png">
                                                      </a>
                                                   </li>
                                                </ul>
                                             </td>
                                          </tr>
                                          <tr>
                                             <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="10"></td>
                                          </tr>
                                          <tr>
                                             <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                                <ul style="list-style:none;margin:0;padding:0">
                                                   <li style="display:inline-block">
                                                      <a style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://facebook.com/vourest&amp;source=gmail&amp;ust=1539830594941000&amp;usg=AFQjCNGWeiiiMPqpyJLEsoW2fEKq_Eg8Qg"><img class="m_6657055476784441913CToWUd CToWUd" alt="Vourest" style="border:0 none;min-height:24px;line-height:100%;outline:none;text-decoration:none;width:24px" src="https://ci6.googleusercontent.com/proxy/UYqtC9qkq_XpUedDcScV0_N-nAonYryT_wwDHs31W8vuGV-V0_kP4AzMtMrITmDMsO_OitSp1iIT7XAmsUK6fnd8-sjaBi-ucEC67WT22caYr7dLrvuv=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/facebook.png">
                                                      </a>
                                                   </li>
                                                   <li style="display:inline-block">
                                                      <a style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://twitter.com/vourest&amp;source=gmail&amp;ust=1539830594942000&amp;usg=AFQjCNFU-RYCdNoEDIlYotUjxZSzGiS4MQ"><img class="m_6657055476784441913CToWUd CToWUd" alt="Twitter" style="border:0 none;min-height:24px;line-height:100%;outline:none;text-decoration:none;width:24px" src="https://ci5.googleusercontent.com/proxy/TgfT1-1qs4avv8E-OZyqZdfih8qJCS0txS6VqWbLTfpdNFXt60CZql-kQm0fLCO2_o8SeT-OGxWAGWGenLAbFx1DstFEivFgEqxwd6ihiHEx6kNBgdg=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/twitter.png">
                                                      </a>
                                                   </li>
                                                   <li style="display:inline-block">
                                                      <a style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://plus.google.com/vourest&amp;source=gmail&amp;ust=1539830594942000&amp;usg=AFQjCNE1SKsd-68QX8FmISjpBEY1horRPQ"><img class="m_6657055476784441913CToWUd CToWUd" alt="Google+" style="border:0 none;min-height:24px;line-height:100%;outline:none;text-decoration:none;width:24px" src="https://ci4.googleusercontent.com/proxy/48wLMKkSwyfDRrQSmUtMUYCNjEmBA_tQyx3KLqDOsSAIjUal-_3mf44a7KWyYgzBh-bnKWMRXoERhNRnIsccDR66f919SF31oZ9peafEykcyFl2pQx4xC1Q=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/googlePlus.png">
                                                      </a>
                                                   </li>
                                                   <li style="display:inline-block">
                                                      <a style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://www.youtube.com/vourest&amp;source=gmail&amp;ust=1539830594942000&amp;usg=AFQjCNEfmZGNe04AZP2K-64HjMoAjJAa9g"><img class="m_6657055476784441913CToWUd CToWUd" alt="Youtube" style="border:0 none;min-height:24px;line-height:100%;outline:none;text-decoration:none;width:24px" src="https://ci3.googleusercontent.com/proxy/Q5q5jkxkJUFlHGNmBAc9y8IEGTv9IsgBazIY7mm9ZrxTb0eTqg6PkCCLl_zqfjXSJZSnOXOQB5CDwB_hTi8jgp-w92qEw5gwCne9ylDzHgjDvxeiCD4=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/youtube.png">
                                                      </a>
                                                   </li>
                                                   <li style="display:inline-block">
                                                      <a style="color:#a30046!important;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=http://instagram.com/vourest&amp;source=gmail&amp;ust=1539830594942000&amp;usg=AFQjCNFYprz1ONgwlv6RIGRZq7RtmnwI0Q"><img class="m_6657055476784441913CToWUd CToWUd" alt="Instagram" style="border:0 none;min-height:24px;line-height:100%;outline:none;text-decoration:none;width:24px" src="https://ci3.googleusercontent.com/proxy/QdmvDRc8AuZp6j2m8-hyc0DYdFQqJRi20zQsEjg2s5iwHTgl4uMmvuRLgcWUsADHhuubueJMnn8gjQiwCG1bDh7q7ek7NgRU3UvzwhIBHVb6u5k2M69HWA=s0-d-e1-ft#http://www.vourest.com/assets/Vourest/pages/img/mail/instagram.png">
                                                      </a>
                                                   </li>
                                                </ul>
                                             </td>
                                          </tr>
                                       </tbody>
                                    </table>
                                 </td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="15"></td>
                              </tr>
                           </tbody>
                        </table>
                     </td>
                  </tr>
                  <tr>
                     <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                        <table style="border-collapse:collapse;border-spacing:0;border-top-color:#ccc;border-top-style:solid;border-top-width:2px;margin:0;padding:0;table-layout:fixed" width="100%">
                           <tbody>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="2"></td>
                              </tr>
                           </tbody>
                        </table>
                     </td>
                  </tr>
                  <tr>
                     <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                        <table style="border-collapse:collapse;border-spacing:0;font-size:10px;margin:0;padding:0;text-align:center" width="100%">
                           <tbody>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="10"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="5"></td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                    <p style="color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0">
                                       Harap jangan membalas e-mail ini, karena e-mail ini dikirimkan secara otomatis oleh sistem.
                                    </p>
                                 </td>
                              </tr>
                              <tr>
                                 <td style="border-collapse:collapse;border-spacing:0;color:#999;font-family:\'Source Sans Pro\',sans-serif;line-height:1.5;margin:0;padding:0" height="15"></td>
                              </tr>
                           </tbody>
                        </table>
                     </td>
                  </tr>
               </tbody>
            </table>';
    }

    public function htmlDetailOrder($id, $status){
        $data = Transaction::where([['id_transaction', $id]])->with(
            'user.city.province',
            'outlet.city')->first();

        if ($data['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $data['id_transaction'])->first();
            $qrTest = $detail['order_id'];
        } elseif ($data['trasaction_type'] == 'Delivery') {
            $detail = TransactionShipment::with('city.province')->where('id_transaction', $data['id_transaction'])->first();
        }

        $data['detail'] = $detail;

        if($status == 'Expired'){
            $data['status'] = 'Your order has expired';
        }elseif ($status == 'Denied'){
            $data['status'] = 'Your order has been rejected';
        }elseif ($status == 'Pending'){
            $data['status'] = 'Your order is pending';
        }elseif ($status == 'Order Accepted'){
            $data['status'] = 'Your order is accepted';
        }elseif ($status == 'Order Ready'){
            $data['status'] = 'Your order is ready';
        }elseif ($status == 'Order Reject'){
            $data['status'] = 'Your order is rejected';
        }elseif ($status == 'Order Taken'){
            $data['status'] = 'Your order is taken';
        }

        $html = view('transaction::email.detail_order')->with(compact('data'))->render();
        return $html;
    }

    public function htmlDetailTrxSuccess($id){
        $list = Transaction::where('transactions.id_transaction', $id);
        $list = $list->leftJoin('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
            ->with(
        // 'user.city.province',
            'productTransaction.product.product_category',
            'productTransaction.modifiers',
            'productTransaction.product.product_photos',
            'productTransaction.product.product_discounts',
            'transaction_payment_offlines',
            'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_pickup_go_send.transaction_pickup_update',
            'outlet.city')->first();

        if(!$list){
            return MyHelper::checkGet([],'empty');
        }
        $list = $list->toArray();
        $label = [];
        $label2 = [];
        $product_count=0;
        $list['product_transaction'] = MyHelper::groupIt($list['product_transaction'],'id_brand',null,function($key,&$val) use (&$product_count){
            $product_count += array_sum(array_column($val,'transaction_product_qty'));
            $brand = Brand::select('name_brand')->find($key);
            if(!$brand){
                return 'No Brand';
            }
            return $brand->name_brand;
        });
        $cart = $list['transaction_subtotal'] + $list['transaction_shipment'] + $list['transaction_service'] + $list['transaction_tax'] - $list['transaction_discount'];

        $list['transaction_carttotal'] = $cart;
        $list['transaction_item_total'] = $product_count;

        $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
        $exp   = explode(',', $order);
        $exp2   = explode(',', $order);

        foreach ($exp as $i => $value) {
            if ($exp[$i] == 'subtotal') {
                unset($exp[$i]);
                unset($exp2[$i]);
                continue;
            }

            if ($exp[$i] == 'tax') {
                $exp[$i] = 'transaction_tax';
                $exp2[$i] = 'transaction_tax';
                array_push($label, 'Tax');
                array_push($label2, 'Tax');
            }

            if ($exp[$i] == 'service') {
                $exp[$i] = 'transaction_service';
                $exp2[$i] = 'transaction_service';
                array_push($label, 'Service Fee');
                array_push($label2, 'Service Fee');
            }

            if ($exp[$i] == 'shipping') {
                if ($list['trasaction_type'] == 'Pickup Order') {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                } else {
                    $exp[$i] = 'transaction_shipment';
                    $exp2[$i] = 'transaction_shipment';
                    array_push($label, 'Delivery Cost');
                    array_push($label2, 'Delivery Cost');
                }
            }

            if ($exp[$i] == 'discount') {
                $exp2[$i] = 'transaction_discount';
                array_push($label2, 'Discount');
                unset($exp[$i]);
                continue;
            }

            if (stristr($exp[$i], 'empty')) {
                unset($exp[$i]);
                unset($exp2[$i]);
                continue;
            }
        }

        switch ($list['trasaction_payment_type']) {
            case 'Balance':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get()->toArray();
                if ($multiPayment) {
                    foreach ($multiPayment as $keyMP => $mp) {
                        switch ($mp['type']) {
                            case 'Balance':
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->first();
                                if ($log['balance'] < 0) {
                                    $list['balance'] = $log['balance'];
                                    $list['check'] = 'tidak topup';
                                } else {
                                    $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                                    $list['check'] = 'topup';
                                }
                                $list['payment'][] = [
                                    'name'      => 'Balance',
                                    'amount'    => $list['balance']
                                ];
                                break;
                            case 'Manual':
                                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                                $list['payment'] = $payment;
                                $list['payment'][] = [
                                    'name'      => 'Cash',
                                    'amount'    => $payment['payment_nominal']
                                ];
                                break;
                            case 'Midtrans':
                                $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $list['payment'][] = $payment;
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $list['payment'][] = $payment;
                                break;
                            case 'IPay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $list['payment'][] = $payment;
                                break;
                            case 'Offline':
                                $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                                foreach ($payment as $key => $value) {
                                    $list['payment'][$key] = [
                                        'name'      => $value['payment_bank'],
                                        'amount'    => $value['payment_amount']
                                    ];
                                }
                                break;
                            default:
                                $list['payment'][] = [
                                    'name'      => null,
                                    'amount'    => null
                                ];
                                break;
                        }
                    }
                } else {
                    $log = LogBalance::where('id_reference', $list['id_transaction'])->first();
                    if ($log['balance'] < 0) {
                        $list['balance'] = $log['balance'];
                        $list['check'] = 'tidak topup';
                    } else {
                        $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                        $list['check'] = 'topup';
                    }
                    $list['payment'][] = [
                        'name'      => 'Balance',
                        'amount'    => $list['balance']
                    ];
                }
                break;
            case 'Manual':
                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                $list['payment'] = $payment;
                $list['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['payment_nominal']
                ];
                break;
            case 'Midtrans':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Midtrans'){
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Ovo':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Ovo'){
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Ipay88':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'IPay88'){
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Offline':
                $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                foreach ($payment as $key => $value) {
                    $list['payment'][$key] = [
                        'name'      => $value['payment_bank'],
                        'amount'    => $value['payment_amount']
                    ];
                }
                break;
            default:
                $list['payment'][] = [
                    'name'      => null,
                    'amount'    => null
                ];
                break;
        }

        array_splice($exp, 0, 0, 'transaction_subtotal');
        array_splice($label, 0, 0, 'Cart Total');

        array_splice($exp2, 0, 0, 'transaction_subtotal');
        array_splice($label2, 0, 0, 'Cart Total');

        array_values($exp);
        array_values($label);

        array_values($exp2);
        array_values($label2);

        $imp = implode(',', $exp);
        $order_label = implode(',', $label);

        $imp2 = implode(',', $exp2);
        $order_label2 = implode(',', $label2);

        $detail = [];

        if ($list['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $list['id_transaction'])->first()->toArray();
            if($detail){
                $qr      = $detail['order_id'].strtotime($list['transaction_date']);

                $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
                $qrCode =   html_entity_decode($qrCode);

                $newDetail = [];
                foreach($detail as $key => $value){
                    $newDetail[$key] = $value;
                    if($key == 'order_id'){
                        $newDetail['order_id_qrcode'] = $qrCode;
                    }
                }

                $detail = $newDetail;
            }
        } elseif ($list['trasaction_type'] == 'Delivery') {
            $detail = TransactionShipment::with('city.province')->where('id_transaction', $list['id_transaction'])->first();
        }

        $list['detail'] = $detail;
        $list['order'] = $imp;
        $list['order_label'] = $order_label;

        $list['order_v2'] = $imp2;
        $list['order_label_v2'] = $order_label2;

        $list['date'] = $list['transaction_date'];
        $list['type'] = 'trx';

        $result = [
            'id_transaction'                => $list['id_transaction'],
            'transaction_receipt_number'    => $list['transaction_receipt_number'],
            'transaction_date'              => date('d M Y H:i', strtotime($list['transaction_date'])),
            'trasaction_type'               => $list['trasaction_type'],
            'transaction_grandtotal'        => MyHelper::requestNumber($list['transaction_grandtotal'],'_CURRENCY'),
            'transaction_subtotal'          => MyHelper::requestNumber($list['transaction_subtotal'],'_CURRENCY'),
            'transaction_discount'          => MyHelper::requestNumber($list['transaction_discount'],'_CURRENCY'),
            'transaction_cashback_earned'   => MyHelper::requestNumber($list['transaction_cashback_earned'],'_POINT'),
            'trasaction_payment_type'       => $list['trasaction_payment_type'],
            'transaction_payment_status'    => $list['transaction_payment_status'],
            'outlet'                        => [
                'outlet_name'       => $list['outlet']['outlet_name'],
                'outlet_address'    => $list['outlet']['outlet_address']
            ]
        ];

        if ($list['trasaction_payment_type'] != 'Offline') {
            $result['detail'] = [
                'order_id_qrcode'   => $list['detail']['order_id_qrcode'],
                'order_id'          => $list['detail']['order_id'],
                'pickup_type'       => $list['detail']['pickup_type'],
                'pickup_date'       => date('d F Y', strtotime($list['detail']['pickup_at'])),
                'pickup_time'       => ($list['detail']['pickup_type'] == 'right now') ? 'RIGHT NOW' : date('H : i', strtotime($list['detail']['pickup_at'])),
                'pickup_at'         => $list['detail']['pickup_at']
            ];
            if (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Cancelled') {
                unset($result['detail']['order_id_qrcode']);
                unset($result['detail']['order_id']);
                unset($result['detail']['pickup_time']);
                $result['transaction_status'] = 0;
                $result['transaction_status_text'] = 'ORDER ANDA DIBATALKAN';
            } elseif (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Pending') {
                unset($result['detail']['order_id_qrcode']);
                unset($result['detail']['order_id']);
                unset($result['detail']['pickup_time']);
                $result['transaction_status'] = 6;
                $result['transaction_status_text'] = 'MENUNGGU PEMBAYARAN';
            } elseif($list['detail']['reject_at'] != null) {
                unset($result['detail']['order_id_qrcode']);
                unset($result['detail']['order_id']);
                unset($result['detail']['pickup_time']);
                $result['transaction_status'] = 0;
                $result['transaction_status_text'] = 'ORDER ANDA DITOLAK';
            } elseif($list['detail']['taken_by_system_at'] != null) {
                $result['transaction_status'] = 1;
                $result['transaction_status_text'] = 'ORDER SELESAI';
            } elseif($list['detail']['taken_at'] != null) {
                $result['transaction_status'] = 2;
                $result['transaction_status_text'] = 'ORDER SUDAH DIAMBIL';
            } elseif($list['detail']['ready_at'] != null) {
                $result['transaction_status'] = 3;
                $result['transaction_status_text'] = 'ORDER SUDAH SIAP';
            } elseif($list['detail']['receive_at'] != null) {
                $result['transaction_status'] = 4;
                $result['transaction_status_text'] = 'ORDER SEDANG DIPROSES';
            } else {
                $result['transaction_status'] = 5;
                $result['transaction_status_text'] = 'ORDER PENDING';
            }
            if ($list['transaction_pickup_go_send']) {
                // $result['transaction_status'] = 5;
                $result['delivery_info'] = [
                    'driver' => null,
                    'delivery_status' => '',
                    'delivery_address' => $list['transaction_pickup_go_send']['destination_address']?:'',
                    'delivery_address_note' => $list['transaction_pickup_go_send']['destination_note']?:'',
                    'booking_status' => 0,
                    'cancelable' => 1,
                    'go_send_order_no' => $list['transaction_pickup_go_send']['go_send_order_no']?:'',
                    'live_tracking_url' => $list['transaction_pickup_go_send']['live_tracking_url']?:''
                ];

                if($list['transaction_pickup_go_send']['go_send_id']){
                    $result['delivery_info']['booking_status'] = 1;
                }
                switch (strtolower($list['transaction_pickup_go_send']['latest_status'])) {
                    case 'finding driver':
                    case 'confirmed':
                        $result['delivery_info']['delivery_status'] = 'Sedang mencari driver';
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        break;
                    case 'driver allocated':
                    case 'allocated':
                        $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                        $result['transaction_status_text']          = 'DRIVER DITEMUKAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        break;
                    case 'enroute pickup':
                    case 'out_for_pickup':
                        $result['delivery_info']['delivery_status'] = 'Driver dalam perjalanan menuju Outlet';
                        $result['transaction_status_text']          = 'DRIVER SEDANG MENUJU OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['delivery_info']['cancelable'] = 1;
                        break;
                    case 'enroute drop':
                    case 'out_for_delivery':
                        $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                        $result['transaction_status_text']          = 'PROSES PENGANTARAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'completed':
                    case 'delivered':
                        $result['transaction_status'] = 2;
                        $result['transaction_status_text']          = 'ORDER SUDAH DIAMBIL';
                        $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'cancelled':
                        $result['delivery_info']['booking_status'] = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN DIBATALKAN';
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        break;
                    case 'driver not found':
                    case 'no_driver':
                        $result['delivery_info']['booking_status']  = 0;
                        $result['transaction_status_text']          = 'DRIVER TIDAK DITEMUKAN';
                        $result['delivery_info']['delivery_status'] = 'Driver tidak ditemukan';
                        $result['delivery_info']['cancelable']      = 0;
                        break;
                }
            }
        }

        $discount = 0;
        $quantity = 0;
        $keynya = 0;
        foreach ($list['product_transaction'] as $keyTrx => $valueTrx) {
            $result['product_transaction'][$keynya]['brand'] = $keyTrx;
            foreach ($valueTrx as $keyProduct => $valueProduct) {
                $quantity = $quantity + $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']              = $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']         = '@'.MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal']        = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']             = $valueProduct['transaction_product_note'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']         = $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']              = $valueProduct['product']['product_name'];
                $discount = $discount + $valueProduct['transaction_product_discount'];
                foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_name']   = $valueMod['text'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_qty']    = $valueMod['qty'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_price']  = MyHelper::requestNumber($valueMod['transaction_product_modifier_price'],'_CURRENCY');
                }
            }
            $keynya++;
        }

        $result['payment_detail'][] = [
            'name'      => 'Subtotal',
            'desc'      => $quantity . ' items',
            'amount'    => MyHelper::requestNumber($list['transaction_subtotal'],'_CURRENCY')
        ];

        $p = 0;
        if (!empty($list['transaction_vouchers'])) {
            foreach ($list['transaction_vouchers'] as $valueVoc) {
                $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                $result['payment_detail'][] = [
                    'name'          => 'Discount',
                    'desc'          => $valueVoc['deals_voucher']['voucher_code'],
                    "is_discount"   => 1,
                    'amount'        => MyHelper::requestNumber($discount,'_CURRENCY')
                ];
            }
        }

        if (!empty($list['promo_campaign_promo_code'])) {
            $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
            $result['payment_detail'][] = [
                'name'          => 'Discount',
                'desc'          => $list['promo_campaign_promo_code']['promo_code'],
                "is_discount"   => 1,
                'amount'        => MyHelper::requestNumber($discount,'_CURRENCY')
            ];
        }

        $result['promo']['discount'] = $discount;
        $result['promo']['discount'] = MyHelper::requestNumber($discount,'_CURRENCY');

        if ($list['trasaction_payment_type'] != 'Offline') {
            if ($list['transaction_payment_status'] == 'Cancelled') {
                $statusOrder[] = [
                    'text'  => 'Pesanan Anda dibatalkan karena pembayaran gagal',
                    'date'  => $list['void_date']
                ];
            }
            elseif ($list['transaction_payment_status'] == 'Pending') {
                $statusOrder[] = [
                    'text'  => 'Menunggu konfirmasi pembayaran',
                    'date'  => $list['transaction_date']
                ];
            } else {
                if ($list['detail']['reject_at'] != null) {
                    $statusOrder[] = [
                        'text'  => 'Order rejected',
                        'date'  => $list['detail']['reject_at'],
                        'reason'=> $list['detail']['reject_reason']
                    ];
                }
                if ($list['detail']['taken_by_system_at'] != null) {
                    $statusOrder[] = [
                        'text'  => 'Pesanan Anda sudah selesai',
                        'date'  => $list['detail']['taken_by_system_at']
                    ];
                }
                if ($list['detail']['taken_at'] != null) {
                    $statusOrder[] = [
                        'text'  => 'Pesanan Anda sudah diambil',
                        'date'  => $list['detail']['taken_at']
                    ];
                }
                if ($list['detail']['ready_at'] != null) {
                    $statusOrder[] = [
                        'text'  => 'Pesanan Anda sudah siap ',
                        'date'  => $list['detail']['ready_at']
                    ];
                }
                if (isset($list['transaction_pickup_go_send'])) {
                    foreach ($list['transaction_pickup_go_send']['transaction_pickup_update'] as $valueGosend) {
                        switch (strtolower($valueGosend['status'])) {
                            case 'finding driver':
                            case 'confirmed':
                                $statusOrder[] = [
                                    'text'  => 'Sedang mencari driver',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                            case 'driver allocated':
                            case 'allocated':
                                $statusOrder[] = [
                                    'text'  => 'Driver ditemukan',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                            case 'enroute pickup':
                            case 'out_for_pickup':
                                $statusOrder[] = [
                                    'text'  => 'Driver dalam perjalanan menuju Outlet',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                            case 'enroute drop':
                            case 'out_for_delivery':
                                $statusOrder[] = [
                                    'text'  => 'Driver mengantarkan pesanan',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                            case 'completed':
                            case 'delivered':
                                $statusOrder[] = [
                                    'text'  => 'Pesanan sudah diterima Customer',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                            case 'cancelled':
                                $statusOrder[] = [
                                    'text'  => 'Pengantaran dibatalkan',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                            case 'driver not found':
                            case 'no_driver':
                                $statusOrder[] = [
                                    'text'  => 'Driver tidak ditemukan',
                                    'date'  => $valueGosend['created_at']
                                ];
                                break;
                        }
                    }
                }
                if ($list['detail']['receive_at'] != null) {
                    $statusOrder[] = [
                        'text'  => 'Pesanan Anda sudah diterima',
                        'date'  => $list['detail']['receive_at']
                    ];
                }
                $statusOrder[] = [
                    'text'  => 'Pesanan Anda menunggu konfirmasi',
                    'date'  => $list['transaction_date']
                ];
            }

            usort($statusOrder, function($a1, $a2) {
                $v1 = strtotime($a1['date']);
                $v2 = strtotime($a2['date']);
                return $v2 - $v1; // $v2 - $v1 to reverse direction
            });

            foreach ($statusOrder as $keyStatus => $status) {
                $result['detail']['detail_status'][$keyStatus] = [
                    'text'  => $status['text'],
                    'date'  => date('d F Y H:i', strtotime($status['date']))
                ];
                if ($status['text'] == 'Order rejected') {
                    $result['detail']['detail_status'][$keyStatus]['reason'] = $list['detail']['reject_reason'];
                }
            }
        }

        foreach ($list['payment'] as $key => $value) {
            if ($value['name'] == 'Balance') {
                $result['transaction_payment'][$key] = [
                    'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                    'is_balance'=> 1,
                    'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                ];
            } else {
                $result['transaction_payment'][$key] = [
                    'name'      => $value['name'],
                    'amount'    => MyHelper::requestNumber($value['amount'],'_CURRENCY')
                ];
            }
        }

        $data = $result;
        $html = view('transaction::email.detail_transaction_success')->with(compact('data'))->render();
        return $html;
    }

    public function kirimOutlet($receipt)
    {
        $check = Transaction::select('id_transaction', 'id_user', 'id_outlet', 'transaction_receipt_number', 'transaction_subtotal', 'transaction_shipment', 'transaction_service', 'transaction_discount', 'transaction_tax', 'transaction_grandtotal', 'trasaction_payment_type', 'transaction_payment_status', 'created_at')->where('transaction_receipt_number', $receipt)->first();
        if (empty($check)) {
            return ['status' => 'fail', 'messages' => ['Transaction not found']];
        } else {
			$check = $check->toArray();
		}

        $detail = TransactionPickup::select('order_id', 'pickup_at')->where('id_transaction', $check['id_transaction'])->first();

		if (empty($detail)) {
            return ['status' => 'fail', 'messages' => ['Transaction pickup detail not found']];
        } else {
			$detail = $detail->toArray();
		}

        $check['transaction_payment_type'] = $check['trasaction_payment_type'];
        unset($check['trasaction_payment_type']);

        $user = User::select('name', 'phone')->where('id', $check['id_user'])->first();

		if (empty($user)) {
            return ['status' => 'fail', 'messages' => ['User not found']];
        } else {
			$user = $user->toArray();
		}

        $outlet = Outlet::select('outlet_code', 'outlet_name', 'id_city')->with('city')->where('id_outlet', $check['id_outlet'])->first();

		if (empty($outlet)) {
            return ['status' => 'fail', 'messages' => ['Outlet not found']];
        } else {
			$outlet = $outlet->toArray();
		}

        $outlet['city_name'] = $outlet['city']['city_name'];
        unset($outlet['city']);

        $dataOutlet = [
            "outlet_code" => $outlet['outlet_code'],
            "outlet_name" => $outlet['outlet_name'],
            "city" => $outlet['city_name'],
        ];

        $payment = [];
        if ($check['transaction_payment_type'] == 'Midtrans') {
            $payment = TransactionPaymentMidtran::select('payment_type', 'gross_amount', 'transaction_status','bank')->where('id_transaction', $check['id_transaction'])->first();

			if (empty($payment)) {
				return ['status' => 'fail', 'messages' => ['Payment not found']];
			} else {
				$payment = $payment->toArray();
			}
        }

        $product = TransactionProduct::select('id_product', 'transaction_product_qty', 'transaction_product_price', 'transaction_product_subtotal')->with('product')->where('id_transaction', $check['id_transaction'])->get()->toArray();


        $dataProduct = [];

        foreach ($product as $key => $value) {
            $pro = [
                'product_code' => $value['product']['product_code'],
                'product_name' => $value['product']['product_name'],
                'price'        => $value['transaction_product_price'],
                'qty'          => $value['transaction_product_qty'],
                'subtotal'     => $value['transaction_product_subtotal']
            ];

            array_push($dataProduct, $pro);
        }

        $check['pickup'] = $detail;
        $check['product'] = $dataProduct;
        $check['user'] = $user;
        $check['outlet'] = $dataOutlet;
        $check['payment'] = $payment;

		$requestformat = array();
		$requestformat['store_code'] = $check['outlet']['outlet_code'];
		$requestformat['trx_id'] = $check['transaction_receipt_number'];
		$requestformat['pickup_time'] = $check['pickup']['pickup_at'];
		$requestformat['date_time'] = $check['created_at'];
		$requestformat['payments'] = array();

		$isinya = array();
		$isinya['type'] = str_replace('_',' ', $check['payment']['payment_type']);
		$isinya['name'] = $check['payment']['bank'];
		$isinya['nominal'] = $check['payment']['gross_amount'];

		array_push($requestformat['payments'], $isinya);

		$requestformat['total'] = $check['transaction_subtotal'];
		$requestformat['service'] = $check['transaction_service'];
		$requestformat['tax'] = $check['transaction_tax'];
		$requestformat['discount'] = $check['transaction_discount'];
		$requestformat['grand_total'] = $check['transaction_grandtotal'];
		$requestformat['menu'] = array();

		foreach($check['product'] as $prod){
			$isinya = array();
			$isinya['plu_id'] = $prod['product_code'];
			$isinya['name'] = $prod['product_name'];
			$isinya['price'] = $prod['price'];
			$isinya['qty'] = $prod['qty'];
			$isinya['category'] = "";

			array_push($requestformat['menu'], $isinya);
		}

		$requestformat['member']['name'] = $user['name'];
		$requestformat['member']['phone'] = $user['phone'];

		// return $requestformat;

		$client = new Client;
		$params = array();
		$params['client_id'] = $this->oauth_id;
		$params['client_secret'] = $this->oauth_secret;

        $content = array(
            'headers' => [
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ],
            'form_params' => $params
        );

        try {
            $response =  $client->request('POST', $this->url_oauth, $content);

            $res = json_decode($response->getBody(), true);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            try{

                if($e->getResponse()){
                    $response = $e->getResponse()->getBody()->getContents();

                    $error = json_decode($response, true);

                    if(!$error) {
                        return $e->getResponse()->getBody();
                    } else {
                        return $error;
                    }
                } else return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];

            } catch(Exception $e) {
                return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
            }
        }

		if($res){
			$client = new Client;

			$content = array(
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization'  => $res['type'].' '.$res['token'],
				],
				'json' => $requestformat
			);

			try {
				$response =  $client->request('POST', $this->url_kirim, $content);

				return json_decode($response->getBody(), true);
			}
			catch (\GuzzleHttp\Exception\RequestException $e) {
				try{

					if($e->getResponse()){
						$response = $e->getResponse()->getBody()->getContents();

						$error = json_decode($response, true);

						if(!$error) {
							return $e->getResponse()->getBody();
						} else {
							return $error;
						}
					} else return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];

				} catch(Exception $e) {
					return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
				}
			}
		}

        return ['status' => 'success'];
    }

    public function kirimUrl($data)
    {
        if (isset($data['id_user'])) {
            unset($data['id_user']);
        }

        if (isset($data['id_outlet'])) {
            unset($data['id_outlet']);
        }

        if (isset($data['id_transaction'])) {
            unset($data['id_transaction']);
        }

        if (isset($data['transaction_payment_type'])) {
            unset($data['transaction_payment_type']);
        }

        if (isset($data['transaction_shipment'])) {
            unset($data['transaction_shipment']);
        }

        $client = new Client;
        $content = array(
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ],
            'json' => (array) $data
        );

        try {
            $response =  $client->request('POST', $this->url_kirim, $content);
            return json_decode($response->getBody(), true);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            try{

                if($e->getResponse()){
                    $response = $e->getResponse()->getBody()->getContents();

                    $error = json_decode($response, true);

                    if(!$error) {
                        return $e->getResponse()->getBody();
                    } else {
                        return $error;
                    }
                } else return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];

            } catch(Exception $e) {
                return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
            }
        }
    }
}
