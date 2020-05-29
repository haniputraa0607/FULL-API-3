<?php

namespace Modules\ShopeePay\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Routing\Controller;

class ShopeePayController extends Controller
{
    public function __construct()
    {
        $this->base_url      = env('SHOPHEEPAY_BASE_URL');
        $this->client_id     = env('SHOPHEEPAY_CLIENT_ID');
        $this->client_secret = env('SHOPHEEPAY_CLIENT_SECRET');
        $this->currency      = env('SHOPHEEPAY_CURRENCY', 'IDR');
        $this->return_url    = env('SHOPHEEPAY_RETURN_URL', );
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
        $post = $request->post();

    }

    /**
     * signing data and send request
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function send($url, $data)
    {
        $header = [
            'X-Airpay-ClientId' => '', // client id
            'X-Airpay-Req-H'    => '', // signature
        ];
        $result = MyHelper::postWithTimeout($url, null, $data, 0, $header, 30);
        return $result;
    }
    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataOrder($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => '',
            'amount'               => '',
            'currency'             => $this->currency,
            'return_url'           => $this->return_url,
            'point_of_initiation'  => 'app',
            'validity_period'      => 1200,
            'additional_info'      => '{}',
        ];
        switch ($type) {
            case 'trx':
                $trx = Transaction::where('id_transaction', $id_reference)->join('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')->with(['outlet' => function ($query) {
                    $query->select('outlet_code', 'id_outlet');
                }])->first();
                if (!$trx) {
                    $errors = ['Transaction not found'];
                    return false;
                }
                $data['payment_reference_id'] = $trx->transaction_receipt_number;
                $data['merchant_ext_id']      = $trx->outlet->outlet_code;
                $data['store_ext_id']         = $trx->outlet->outlet_code;
                $data['amount']               = $trx->amount;
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Create new payment request
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function pay(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/order/create';
        $postData = $this->generateDataOrder(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataCheckStatus($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => ''
        ];
        switch ($type) {
            case 'trx':
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function checkStatus(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/payment/check';
        $postData = $this->generateDataCheckStatus(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataRefund($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => ''
        ];
        switch ($type) {
            case 'trx':
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function refund(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/refund/create';
        $postData = $this->generateDataCheckStatus(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataVoid($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => ''
        ];
        switch ($type) {
            case 'trx':
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function void(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/void/create';
        $postData = $this->generateDataCheckStatus(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataSetMerchant($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => ''
        ];
        switch ($type) {
            case 'trx':
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function setMerchant(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/merchant/set';
        $postData = $this->generateDataCheckStatus(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataSetStore($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => ''
        ];
        switch ($type) {
            case 'trx':
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function setStore(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/void/create';
        $postData = $this->generateDataCheckStatus(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }

    /**
     * generate data to be send to Shopheepay
     * @param  Integer $id_reference id_transaction/id_deals_user
     * @param  string $type type of transaction ('trx'/'deals')
     * @return Array       array formdata
     */
    public function generateDataTransactionList($id_reference, $type = 'trx', $payment_method = null, &$errors)
    {
        $data = [
            'request_id'           => '',
            'payment_reference_id' => '',
            'merchant_ext_id'      => '',
            'store_ext_id'         => ''
        ];
        switch ($type) {
            case 'trx':
                break;

            default:
                # code...
                break;
        }
    }
    /**
     * Check status payment
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function transactionList(...$params)
    {
        $url      = $this->base_url . 'v3/merchant-host/transaction/void/create';
        $postData = $this->generateDataCheckStatus(...$params);
        if (!$postData) {
            return $postData;
        }
        $response = $this->send($url, $postData);
        return $response;
    }
}
