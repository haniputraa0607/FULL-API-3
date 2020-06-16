<?php

namespace Modules\ShopeePay\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\LogBalance;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Jobs\FraudJob;
use App\Lib\MyHelper;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\ShopeePay\Entities\DealsPaymentShopeePay;
use Modules\ShopeePay\Entities\LogShopeePay;
use Modules\ShopeePay\Entities\ShopeePayMerchant;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;

class ShopeePayController extends Controller
{
    public function __construct()
    {
        $this->point_of_initiation = 'app';
        $this->validity_period     = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
        $this->notif               = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->setting_fraud       = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->autocrm             = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance             = "Modules\Balance\Http\Controllers\BalanceController";
        $this->voucher             = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->promo_campaign      = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->deals_claim         = "Modules\Deals\Http\Controllers\ApiDealsClaim";
    }

    public function __get($key)
    {
        return env('SHOPHEEPAY_' . strtoupper($key));
    }

    /**
     * Listen notification from shopeepay
     * @param  Request $request [description]
     * {
     *    "amount": 10000,
     *    "merchant_ext_1d": "externalmerchant",
     *    "payment_reference_id": "payment-ref-1-must-be-unique",
     *    "payment_status": 1,
     *    "reference_id": "payment-ret-1-must-be-unique",
     *    "store_ext_id": "externalstore",
     *    "terminal_id": "T2903",
     *    "transaction_sn": "200000566633342755",
     *    "user_id_hash": "e1038777a3868f152526ebc19bd899d0a68aabbe87a38a759b1bb9577d84483b"
     * }
     * @return [type]           [description]
     */
    public function notifShopeePay(Request $request)
    {
        $post           = $request->post();
        $header         = $request->header();
        $validSignature = $this->createSignature($post);
        if (($request->header('X-Airpay-Req-H')) != $validSignature) {
            $status_code = 401;
            $response    = [
                'status'   => 'fail',
                'messages' => ['Signature mismatch'],
            ];
            goto end;
        }

        DB::beginTransaction();
        // CHECK ORDER ID
        if (stristr($post['payment_reference_id'], "TRX")) {
            $trx = Transaction::where('transaction_receipt_number', $post['payment_reference_id'])->join('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')->first();
            if (!$trx) {
                $status_code = 404;
                $response    = ['status' => 'fail', 'messages' => ['Transaction not found']];
                goto end;
            }
            if ($trx->amount != $post['amount']) {
                $status_code = 401;
                $response    = ['status' => 'fail', 'messages' => ['Invalid amount']];
                goto end;
            }
            if ($post['payment_status'] == '1') {
                $update = $trx->update(['transaction_payment_status' => 'Completed', 'completed_at' => date('Y-m-d H:i:s')]);
            }
            if (!$update) {
                DB::rollBack();
                $status_code = 500;
                $response    = [
                    'status'   => 'fail',
                    'messages' => ['Failed update payment status'],
                ];
            }

            //inset pickup_at when pickup_type = right now
            if ($trx['trasaction_type'] == 'Pickup Order') {
                $detailTrx = TransactionPickup::where('id_transaction', $trx->id_transaction)->first();
                if ($detailTrx['pickup_type'] == 'right now') {
                    $settingTime = MyHelper::setting('processing_time');
                    if ($settingTime) {
                        $updatePickup = $detailTrx->update(['pickup_at' => date('Y-m-d H:i:s', strtotime('+ ' . $settingTime . 'minutes'))]);
                    } else {
                        $updatePickup = $detailTrx->update(['pickup_at' => date('Y-m-d H:i:s')]);
                    }
                }
            }

            TransactionPaymentShopeePay::where('id_transaction', $trx->id_transaction)->update([
                'transaction_sn' => $post['transaction_sn'] ?? null,
                'payment_status' => $post['payment_status'] ?? null,
                'user_id_hash'   => $post['user_id_hash'] ?? null,
                'terminal_id'    => $post['terminal_id'] ?? null,
            ]);
            DB::commit();

            $trx->load('outlet');
            $trx->load('productTransaction');

            $userData               = User::where('id', $trx['id_user'])->first();
            $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->first()->is_active;

            if ($config_fraud_use_queue == 1) {
                FraudJob::dispatch($userData, $trx, 'transaction')->onConnection('fraudqueue');
            } else {
                $checkFraud = app($this->setting_fraud)->checkFraudTrxOnline($userData, $trx);
            }
            $mid = [
                'order_id'     => $trx['transaction_receipt_number'],
                'gross_amount' => ($trx['amount'] / 100),
            ];
            $send = app($this->notif)->notification($mid, $trx);

            $status_code = 200;
            $response    = ['status' => 'success'];
            $sendPOS     = \App\Lib\ConnectPOS::create()->sendTransaction([$trx['id_transaction']]);
        } else {
            $deals_payment = DealsPaymentShopeePay::where('order_id', $post['payment_reference_id'])->join('deals', 'deals.id_deals', '=', 'deals_payment_shopee_pays.id_deals')->first();

            if (!$deals_payment) {
                $status_code = 404;
                $response    = ['status' => 'fail', 'messages' => ['Transaction not found']];
                goto end;
            }
            if ($deals_payment->amount != $post['amount']) {
                $status_code = 401;
                $response    = ['status' => 'fail', 'messages' => ['Invalid amount']];
                goto end;
            }
            if ($post['payment_status'] == '1') {
                $update = DealsUser::where('id_deals_user', $deals_payment->id_deals_user)->update(['paid_status' => 'Completed']);
            }
            if (!$update) {
                DB::rollBack();
                $status_code = 500;
                $response    = [
                    'status'   => 'fail',
                    'messages' => ['Failed update payment status'],
                ];
                goto end;
            }

            $deals_payment->update([
                'transaction_sn' => $post['transaction_sn'] ?? null,
                'payment_status' => $post['payment_status'] ?? null,
                'user_id_hash'   => $post['user_id_hash'] ?? null,
                'terminal_id'    => $post['terminal_id'] ?? null,
            ]);
            DB::commit();

            $userPhone = User::select('phone')->where('id', $deals_payment->id_user)->pluck('phone')->first();
            $send      = app($this->autocrm)->SendAutoCRM(
                'Payment Deals Success',
                $userPhone,
                [
                    'deals_title'   => $deals_payment->title,
                    'id_deals_user' => $deals_payment->id_deals_user,
                ]
            );
            $status_code = 200;
            $response    = ['status' => 'success'];
        }

        end:
        try {
            LogShopeePay::create([
                'type'                 => 'webhook',
                'id_reference'         => $post['payment_reference_id'],
                'request'              => json_encode($post),
                'request_url'          => url(route('notif_shopeepay')),
                'request_header'       => json_encode($header),
                'response'             => json_encode($response),
                'response_status_code' => $status_code,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed write log to LogShopeePay: ' . $e->getMessage());
        }
        return response()->json($response, $status_code);
    }
    /**
     * Cron set transaction payment status cancel
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function cronCancel()
    {
        $now     = date('Y-m-d H:i:s');
        $expired = date('Y-m-d H:i:s', time() - $this->validity_period);

        $getTrx = Transaction::where('transaction_payment_status', 'Pending')
            ->join('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
            ->where('transaction_date', '<=', $expired)
            ->whereIn('trasaction_payment_type', ['Shopeepay', 'Balance'])
            ->get();

        $count = 0;
        foreach ($getTrx as $key => $singleTrx) {
            $singleTrx->load('outlet_name');

            $productTrx = TransactionProduct::where('id_transaction', $singleTrx->id_transaction)->get();
            if (empty($productTrx)) {
                continue;
            }

            $user = User::where('id', $singleTrx->id_user)->first();
            if (empty($user)) {
                continue;
            }

            // get status from shopeepay
            $status = $this->checkStatus($singleTrx, 'trx', $errors);
            if (!$status) {
                \Log::error('Failed get shopeepay status transaction ' . $singleTrx->transaction_receipt_number . ': ', $errors);
                continue;
            }
            DB::begintransaction();
            // is transaction success?
            if (($status['response']['payment_status'] ?? false) == '1') {
                // void transaction
                $void_reference_id = null;
                $void              = $this->void($singleTrx, 'trx', $errors, $void_reference_id);
                if (!$void) {
                    \Log::error('Failed void transaction ' . $singleTrx->transaction_receipt_number . ': ', $errors);
                    continue;
                }
                if (($void['response']['errcode'] ?? 123) == 0) {
                    $up = TransactionPaymentShopeePay::where('id_transaction', $singleTrx->id_transaction)->update(['void_reference_id' => $void_reference_id]);
                }
            }

            $singleTrx->transaction_payment_status = 'Cancelled';
            $singleTrx->void_date                  = $now;
            $singleTrx->save();

            if (!$singleTrx) {
                DB::rollBack();
                continue;
            }

            //reversal balance
            $logBalance = LogBalance::where('id_reference', $singleTrx->id_transaction)->where('source', 'Transaction')->where('balance', '<', 0)->get();
            foreach ($logBalance as $logB) {
                $reversal = app($this->balance)->addLogBalance($singleTrx->id_user, abs($logB['balance']), $singleTrx->id_transaction, 'Reversal', $singleTrx->transaction_grandtotal);
                if (!$reversal) {
                    DB::rollBack();
                    continue;
                }
                $usere = User::where('id', $singleTrx->id_user)->first();
                $send  = app($this->autocrm)->SendAutoCRM('Transaction Failed Point Refund', $usere->phone,
                    [
                        "outlet_name"      => $singleTrx->outlet_name->outlet_name,
                        "transaction_date" => $singleTrx->transaction_date,
                        'id_transaction'   => $singleTrx->id_transaction,
                        'receipt_number'   => $singleTrx->transaction_receipt_number,
                        'received_point'   => (string) abs($logB['balance']),
                    ]
                );
            }

            // delete promo campaign report
            if ($singleTrx->id_promo_campaign_promo_code) {
                $update_promo_report = app($this->promo_campaign)->deleteReport($singleTrx->id_transaction, $singleTrx->id_promo_campaign_promo_code);
                if (!$update_promo_report) {
                    DB::rollBack();
                    continue;
                }
            }

            // return voucher
            $update_voucher = app($this->voucher)->returnVoucher($singleTrx->id_transaction);
            if (!$update_voucher) {
                DB::rollBack();
                continue;
            }
            $count++;
            DB::commit();

        }
        return response()->json([$count]);
    }

    /**
     * Cron set deals user payment status cancel
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function cronCancelDeals()
    {
        $now       = date('Y-m-d H:i:s');
        $expired   = date('Y-m-d H:i:s', time() - $this->validity_period);

        $getTrx = DealsUser::where('paid_status', 'Pending')
            ->join('deals_payment_shopee_pays', 'deals_users.id_deals_user', '=', 'deals_payment_shopee_pays.id_deals_user')
            ->where('payment_method', 'Shopeepay')
            ->where('claimed_at', '<=', $expired)->get();

        if (empty($getTrx)) {
            return response()->json(['empty']);
        }
        $count = 0;
        foreach ($getTrx as $key => $singleTrx) {

            $user = User::where('id', $singleTrx->id_user)->first();
            if (empty($user)) {
                continue;
            }

            // get status from shopeepay
            $status = $this->checkStatus($singleTrx->id_deals_user, 'deals', $errors);
            if (!$status) {
                \Log::error('Failed get shopeepay status deals user ' . $singleTrx->id_deals_user . ': ', $errors);
                continue;
            }
            DB::begintransaction();
            // is transaction success?
            if (($status['response']['payment_status'] ?? false) == '1') {
                // void transaction
                $void_reference_id = null;
                $void              = $this->void($singleTrx->id_deals_user, 'deals', $errors, $void_reference_id);
                if (!$void) {
                    \Log::error('Failed void deals ' . $singleTrx->id_deals_user . ': ', $errors);
                    continue;
                }
                if (($void['response']['errcode'] ?? 123) == 0) {
                    $up = DealsPaymentShopeePay::where('id_deals_user', $singleTrx->id_deals_user)->update(['void_reference_id' => $void_reference_id]);
                }
            }

            $singleTrx->paid_status = 'Cancelled';
            $singleTrx->save();

            if (!$singleTrx) {
                DB::rollBack();
                continue;
            }

            // revert back deals data
            $deals = Deal::where('id_deals',$singleTrx->id_deals)->first();
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
            $del = app($this->deals_claim)->checkUserClaimed($user, $singleTrx->id_deals, true);

            //reversal balance
            $logBalance = LogBalance::where('id_reference', $singleTrx->id_deals_user)->where('source', 'Deals Balance')->where('balance', '<', 0)->get();
            foreach($logBalance as $logB){
                $reversal = app($this->balance)->addLogBalance( $singleTrx->id_user, abs($logB['balance']), $singleTrx->id_deals_user, 'Deals Reversal', $singleTrx->voucher_price_point?:$singleTrx->voucher_price_cash);
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
        return response()->json([$count]);
    }

    /**
     * The signature is a hash-based message authentication code (HMAC) using the SHA256
     * cryptographic function and the aforementioned secret key, operated on the request JSON.
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function createSignature($data, $custom_key = null)
    {
        $return = rtrim(base64_encode(hex2bin(hash_hmac('sha256', json_encode($data), $custom_key ?: $this->client_secret))), "\n");
        return $return;
    }

    /**
     * Generate unique request id
     * @return String request id
     */
    public function requestId()
    {
        return time() . rand(1000, 9999);
    }

    /**
     * signing data and send request
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function send($url, $data, $logData = null)
    {
        // fill request_id
        $header = [
            'X-Airpay-ClientId' => $this->client_id, // client id
            'X-Airpay-Req-H'    => $this->createSignature($data), // signature
        ];
        $result = MyHelper::postWithTimeout($url, null, $data, 0, $header, 30);
        try {
            if (!$logData) {$logData = [];}
            LogShopeePay::create($logData + [
                'request'              => json_encode($data),
                'request_url'          => $url,
                'request_header'       => json_encode($header),
                'response'             => json_encode($result['response'] ?? []),
                'response_status_code' => $result['status_code'] ?? null
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed write log to LogShopeePay: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Model $reference TransactionPaymentShopeePay/DealsPaymentShopeePay
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataOrder($reference, $type = 'trx', &$errors = null)
    {
        $data = [
            'request_id'           => $this->requestId(),
            'payment_reference_id' => '',
            'merchant_ext_id'      => $this->merchant_ext_id,
            'store_ext_id'         => $this->store_ext_id,
            'amount'               => '',
            'currency'             => $this->currency ?: 'IDR',
            'return_url'           => $this->return_url,
            'point_of_initiation'  => $this->point_of_initiation,
            'validity_period'      => $this->validity_period,
            'additional_info'      => '{}',
        ];
        switch ($type) {
            case 'trx':
                $trx = Transaction::where('id_transaction', $reference->id_transaction)->first();
                if (!$trx) {
                    $errors = ['Transaction not found'];
                    return false;
                }
                $data['payment_reference_id'] = $trx->transaction_receipt_number;
                $data['amount']               = $reference->amount;
                break;

            case 'deals':
                $data['payment_reference_id'] = $reference->order_id;
                $data['amount']               = $reference->amount;
                break;

            default:
                # code...
                break;
        }
        $reference->update($data);
        return $data;
    }

    /**
     * Create new payment request
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function order($reference, $type = 'trx', &$errors = null)
    {
        $url      = $this->base_url . 'v3/merchant-host/order/create';
        $postData = $this->generateDataOrder($reference, $type, $errors);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'order', 'id_reference' => $postData['payment_reference_id']]);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user or model
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataCheckStatus($reference, $type = 'trx', &$errors = null)
    {
        $data = [
            'request_id'           => $this->requestId(),
            'payment_reference_id' => '',
            'merchant_ext_id'      => $this->merchant_ext_id,
            'store_ext_id'         => $this->store_ext_id,
        ];
        switch ($type) {
            case 'trx':
                if (is_numeric($reference)) {
                    $reference = Transaction::where('id_transaction', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['transaction_receipt_number'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['transaction_receipt_number'];
                break;

            case 'deals':
                if (is_numeric($reference)) {
                    $reference = DealsPaymentShopeePay::where('id_deals_user', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['order_id'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['order_id'];
                break;

            default:
                # code...
                break;
        }
        return $data;
    }

    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function checkStatus($reference, $type = 'trx', &$errors = null)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/payment/check';
        $postData = $this->generateDataCheckStatus($reference, $type, $errors);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'check_status', 'id_reference' => $postData['payment_reference_id']]);
        /**
         * $response
         * {
         *     "status_code": 200,
         *     "response": {
         *         "request_id": "15918485088617",
         *         "errcode": 0,
         *         "debug_msg": "success",
         *         "payment_status": 2
         *     }
         * }
         */
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user or model
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataRefund($reference, $type = 'trx', &$errors = null, &$refund_reference_id = null)
    {
        $refund_reference_id = $refund_reference_id ?: time() . rand(10, 99);
        $data                = [
            'request_id'           => $this->requestId(),
            'payment_reference_id' => '',
            'refund_reference_id'  => $refund_reference_id,
            'merchant_ext_id'      => $this->merchant_ext_id,
            'store_ext_id'         => $this->store_ext_id,
        ];
        switch ($type) {
            case 'trx':
                if (is_numeric($reference)) {
                    $reference = Transaction::where('id_transaction', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['transaction_receipt_number'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['transaction_receipt_number'];
                break;

            case 'deals':
                if (is_numeric($reference)) {
                    $reference = DealsPaymentShopeePay::where('id_deals_user', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['order_id'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['order_id'];
                break;

            default:
                # code...
                break;
        }
        return $data;
    }

    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function refund($reference, $type = 'trx', &$errors = null, &$refund_reference_id = null)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/refund/create';
        $postData = $this->generateDataRefund($reference, $type, $errors, $refund_reference_id);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'refund', 'id_reference' => $postData['payment_reference_id']]);
        /**
         * $response
         * {
         *     "status_code": 200,
         *     "response": {
         *         "request_id": "15918456848159",
         *         "errcode": 0,
         *         "debug_msg": "success",
         *         "transaction_list": [
         *             {
         *                 "reference_id": "159184568498",
         *                 "amount": 100,
         *                 "create_time": 1591845687,
         *                 "update_time": 1591845687,
         *                 "transaction_sn": "004165643666456760",
         *                 "status": 3,
         *                 "transaction_type": 15,
         *                 "merchant_ext_id": "1234",
         *                 "terminal_id": "",
         *                 "user_id_hash": "6d65274e5cba19a063ae6e8923e04c877aa228df95c77e87fb81c398800727a0",
         *                 "store_ext_id": "M000"
         *             }
         *         ]
         *     }
         * }
         * {
         *     "status_code": 200,
         *     "response": {
         *         "request_id": "15918508035733",
         *         "errcode": 121,
         *         "debug_msg": "Completed",
         *         "transaction_list": [
         *             {
         *                 "reference_id": "159184568498",
         *                 "amount": 100,
         *                 "create_time": 1591845687,
         *                 "update_time": 1591845687,
         *                 "transaction_sn": "004165643666456760",
         *                 "status": 3,
         *                 "transaction_type": 15,
         *                 "merchant_ext_id": "1234",
         *                 "terminal_id": "",
         *                 "user_id_hash": "6d65274e5cba19a063ae6e8923e04c877aa228df95c77e87fb81c398800727a0",
         *                 "store_ext_id": "M000"
         *             }
         *         ]
         *     }
         * }
         */
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataVoid($reference, $type = 'trx', &$errors = null, &$void_reference_id = null)
    {
        $void_reference_id = $void_reference_id ?: time() . rand(10, 99);
        $data              = [
            'request_id'           => $this->requestId(),
            'payment_reference_id' => '',
            'void_reference_id'    => $void_reference_id,
            'merchant_ext_id'      => $this->merchant_ext_id,
            'store_ext_id'         => $this->store_ext_id,
        ];
        switch ($type) {
            case 'trx':
                if (is_numeric($reference)) {
                    $reference = Transaction::where('id_transaction', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['transaction_receipt_number'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['transaction_receipt_number'];
                break;

            case 'deals':
                if (is_numeric($reference)) {
                    $reference = DealsPaymentShopeePay::where('id_deals_user', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['order_id'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['order_id'];
                break;

            default:
                # code...
                break;
        }
        return $data;
    }

    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function void($reference, $type = 'trx', &$errors = null, &$void_reference_id = null)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/void/create';
        $postData = $this->generateDataVoid($reference, $type, $errors, $void_reference_id);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'void', 'id_reference' => $postData['payment_reference_id']]);
        /**
         * $response
         * {
         *     "status_code": 200,
         *     "response": {
         *         "request_id": "15918459392223",
         *         "errcode": 0,
         *         "debug_msg": "success",
         *         "transaction_list": [
         *             {
         *                 "reference_id": "159184593913",
         *                 "amount": 100,
         *                 "create_time": 1591845939,
         *                 "update_time": 1591845939,
         *                 "transaction_sn": "021110623813340206",
         *                 "status": 3,
         *                 "transaction_type": 26,
         *                 "merchant_ext_id": "1234",
         *                 "terminal_id": "",
         *                 "user_id_hash": "6d65274e5cba19a063ae6e8923e04c877aa228df95c77e87fb81c398800727a0",
         *                 "store_ext_id": "M000"
         *             }
         *         ]
         *     }
         * }
         * {
         *     "status_code": 200,
         *     "response": {
         *         "request_id": "15918506117324",
         *         "errcode": 121,
         *         "debug_msg": "Merchant already voided"
         *     }
         * }
         */
        return $response;
    }

    /**
     * Sync merchant and store to shopeey
     * @return [type] [description]
     */
    public function syncMerchant()
    {
        $merchants = ShopeePayMerchant::all();
        $errors    = [];
        foreach ($merchants as $merchant) {
            $response = $this->setMerchant($merchant, $error = []);
            if ($response['status_code'] == 200) {
                $merchant->update($response['response']['merchant'] ?? []);
            }
            $errors += $error;
        }
        $stores = Outlet::whereNotNull('merchant_ext_id')->with('city')->get();
        foreach ($stores as $store) {
            $response = $this->setStore($store, $error = []);
            $errors += $error;
        }
        if ($errors) {
            \Log::error('Sync merchants error: ');
            \Log::error($errors);
        }
        return true;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataSetMerchant($merchant)
    {
        $data = [
            'request_id'          => $this->requestId(),
            'merchant_name'       => $merchant['merchant_name'],
            'merchant_ext_id'     => $merchant['merchant_ext_id'],
            'postal_code'         => $merchant['postal_code'],
            'city'                => $merchant['city'],
            'mcc'                 => $this->mcc,
            'point_of_initiation' => 0,
            'withdrawal_option'   => (int) $this->withdrawal_option,
            'national_id_type'    => $merchant['national_id_type'],
            'national_id'         => $merchant['national_id'],
            'logo'                => $merchant['logo'] ? base64_encode(file_get_contents($merchant['logo'])) : null,
        ];
        return $data;
    }

    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function setMerchant($merchant)
    {
        $url      = $this->base_url . 'v3/merchant-host/merchant/set';
        $postData = $this->generateDataSetMerchant($merchant);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'set_merchant', 'id_reference' => $postData['merchant_ext_id']]);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataSetStore($store)
    {
        $data = [
            'request_id'          => $this->requestId(),
            'merchant_ext_id'     => $store['merchant_ext_id'],
            'store_ext_id'        => $store['outlet_code'],
            'store_name'          => $store['outlet_name'],
            'phone'               => $store['outlet_phone'],
            'address'             => $store['outlet_address'],
            'postal_code'         => $store['outlet_phone'],
            'city'                => $store['city']['city_name'],
            'gps_longitude'       => (int) ($store['outlet_latitude'] * 100000),
            'gps_latitude'        => (int) ($store['outlet_longitude'] * 10000),
            'point_of_initiation' => 0,
            'mcc'                 => $this->mcc,
            'email'               => $store['outlet_email'],
            'merchant_criteria'   => 'UME',
            'logo'                => null,
        ];
        return $data;
    }

    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function setStore($store)
    {
        $url      = $this->base_url . 'v3/merchant-host/store/set';
        $postData = $this->generateDataSetStore($store);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'set_store', 'id_reference' => $postData['store_ext_id']]);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataTransactionList($from, $to, $last_reference_id = null, $transaction_type_list = null, $limit = null)
    {
        $data = [
            'request_id'            => $this->requestId(),
            'begin_time'            => is_numeric($from) ? $from : strtotime($from),
            'end_time'              => is_numeric($to) ? $from : strtotime($to),
            'last_reference_id'     => $last_reference_id,
            'transaction_type_list' => $transaction_type_list,
            'limit'                 => $limit,
        ];
        return $data;
    }

    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function transactionList(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/list';
        $postData = $this->generateDataTransactionList(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData, ['type' => 'transaction_list', 'id_reference' => 'LIST-' . time()]);
        return $response;
    }
}
