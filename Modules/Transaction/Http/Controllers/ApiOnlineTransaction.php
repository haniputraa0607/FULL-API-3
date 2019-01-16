<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\User;
use App\Http\Models\UserAddress;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\UserOutlet;
use App\Http\Models\TransactionSetting;
use App\Http\Models\FraudSetting;
use App\Http\Models\Configs;
use App\Http\Models\Holiday;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request as RequestGuzzle;
use Guzzle\Http\Message\Response as ResponseGuzzle;
use Guzzle\Http\Exception\ServerErrorResponseException;

use DB;
use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\GoSend;

use Modules\Transaction\Http\Requests\Transaction\NewTransaction;
use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;

class ApiOnlineTransaction extends Controller
{
    public $saveImage = "img/payment/manual/";

    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->balance     = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership  = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm     = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->notif       = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiSettingFraud";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
    }

    public function newTransaction(NewTransaction $request) {
        $post = $request->json()->all();
        $totalPrice = 0;
        $totalWeight = 0;
        $totalDiscount = 0;
        $grandTotal = app($this->setting_trx)->grandTotal();
        $order_id = null;
        $id_pickup_go_send = null;

        if (isset($post['headers'])) {
            unset($post['headers']);
        }

        $dataInsertProduct = [];
        $productMidtrans = [];
        $dataDetailProduct = [];
        $userTrxProduct = [];

        if (isset($post['transaction_date'])) {
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        if (isset($post['transaction_payment_status'])) {
            $post['transaction_payment_status'] = $post['transaction_payment_status'];
        } else {
            $post['transaction_payment_status'] = 'Pending';
        }
  
        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $user = User::with('memberships')->where('id', $id)->first();
    
        if (empty($user)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }

        if (count($user['memberships']) > 0) {
            $post['membership_level']    = $user['memberships'][0]['membership_name'];
            $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
        } else {
            $post['membership_level']    = null;
            $post['membership_promo_id'] = null;
        }

        if ($post['type'] == 'Delivery') {
            $userAddress = UserAddress::where(['id_user' => $id, 'id_user_address' => $post['id_user_address']])->first();
        
            if (empty($userAddress)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Address Not Found']
                ]);
            }
        }
        
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->first();
        if (empty($outlet)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
                ]);
            }
            
        //cek outlet holiday
        $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
        if(count($holiday) > 0){
            foreach($holiday as $i => $holi){
                if($holi['yearly'] == '0'){
                    if($holi['date'] == date('Y-m-d')){
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet Is Closed']
                        ]);
                    }
                }else{
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Outlet Is Closed']
                    ]);
                }
            }
        }

        //cek outlet open - close hour
        if(($outlet['today']['open'] && date('H:i') < date('H:i', strtotime($outlet['today']['open']))) || ($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close'])))){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Is Closed']
            ]);    
        }

        $totalDisProduct = 0;

        // $productDis = $this->countDis($post);
        $productDis = app($this->setting_trx)->discountProduct($post);
        if ($productDis) {
            $totalDisProduct = $productDis;
        }

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                // $post['sub'] = $this->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal'] = array_sum($post['sub']);
                $post['subtotal'] = $post['subtotal'] - $totalDisProduct;
            } elseif ($valueTotal == 'discount') {
                // $post['dis'] = $this->countTransaction($valueTotal, $post);
                $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                if (isset($post['sub']->original['messages'])) {
                    $mes = $post['sub']->original['messages'];

                    if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                        if (isset($post['sub']->original['product'])) {
                            $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                
                $post['discount'] = $post['dis'] + $totalDisProduct;
            } else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        $post['point'] = app($this->setting_trx)->countTransaction('point', $post);
        $post['cashback'] = app($this->setting_trx)->countTransaction('cashback', $post);

        //count some trx user
        $countUserTrx = Transaction::where('id_user', $id)->count();

        $countSettingCashback = TransactionSetting::get();

        if ($countUserTrx < count($countSettingCashback)) {
            $post['cashback'] = $post['cashback'] * $countSettingCashback[$countUserTrx + 1]['cashback_percent'] / 100;

            if ($post['cashback'] > $countSettingCashback[$countUserTrx + 1]['cashback_maximum']) {
                $post['cashback'] = $countSettingCashback[$countUserTrx + 1]['cashback_maximum'];
            }
        } else {
            if (count($user['memberships']) > 0) {
                $post['cashback'] = $post['cashback'] * ($user['memberships'][0]['benefit_cashback_multiplier']) / 100;
            }
        }

        $maxCash = Setting::where('key', 'cashback_maximum')->first();

        if (count($user['memberships']) > 0) {
            $post['point'] = $post['point'] * ($user['memberships'][0]['benefit_point_multiplier']) / 100;

            if($user['memberships'][0]['cashback_maximum']){
                $maxCash['value'] = $user['memberships'][0]['cashback_maximum'];
            }
        }

        $statusCashMax = 'no';

        if (!empty($maxCash) && !empty($maxCash['value'])) {
            $statusCashMax = 'yes';
            $totalCashMax = $maxCash['value'];
        }

        if ($statusCashMax == 'yes') {
            if ($totalCashMax < $post['cashback']) {
                $post['cashback'] = $totalCashMax;
            }
        } else {
            $post['cashback'] = $post['cashback'];
        }

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if (!isset($post['shipping'])) {
            $post['shipping'] = 0;
        }

        if (!isset($post['subtotal'])) {
            $post['subtotal'] = 0;
        }

        if (!isset($post['discount'])) {
            $post['discount'] = 0;
        }

        if (!isset($post['service'])) {
            $post['service'] = 0;
        }

        if (!isset($post['tax'])) {
            $post['tax'] = 0;
        }

        $post['discount'] = -$post['discount'];

        if (isset($post['payment_type']) && $post['payment_type'] == 'Balance') {
            $post['cashback'] = 0;
            $post['point']    = 0;
        }

        //cek free delivery
        $isFree = '0';
        if($post['type'] == 'GO-SEND'){
            $setting = Setting::where('key', 'like', '%free_delivery%')->get();
            if($setting){
                $freeDev = [];
                foreach($setting as $dataSetting){
                    $freeDev[$dataSetting['key']] = $dataSetting['value'];
                }
     
                if(isset($freeDev['free_delivery_type'])){
                    if($freeDev['free_delivery_type'] == 'free' || isset($freeDev['free_delivery_nominal'])){
                        if(isset($freeDev['free_delivery_requirement_type']) && $freeDev['free_delivery_requirement_type'] == 'total item' && isset($freeDev['free_delivery_min_item'])){
                            $totalItem = 0;
                            foreach ($post['item'] as $keyProduct => $valueProduct) {
                                $totalItem += $valueProduct['qty'];
                            }
             
                            if($totalItem >= $freeDev['free_delivery_min_item']){
                                $isFree = '1';
                            }
                        }elseif(isset($freeDev['free_delivery_requirement_type']) && $freeDev['free_delivery_requirement_type'] == 'subtotal' && isset($freeDev['free_delivery_min_subtotal'])){
                            if($post['subtotal'] >= $freeDev['free_delivery_min_subtotal']){
                                $isFree = '1';
                            }
                        }
                    }
                }
            }
        }

        $detailPayment = [
            'subtotal' => $post['subtotal'],
            'shipping' => $post['shipping'],
            'tax'      => $post['tax'],
            'service'  => $post['service'],
            'discount' => $post['discount'],
        ];

        // return $detailPayment;
        $post['grandTotal'] = $post['subtotal'] + $post['discount'] + $post['service'] + $post['tax'] + $post['shipping'];
        // return $post;
        if ($post['type'] == 'Delivery') {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $userAddress['name'],
                    'phone'       => $userAddress['phone'],
                    'address'     => $userAddress['address'],
                    'postal_code' => $userAddress['postal_code']
                ],
            ];

            $dataShipping = [
                'first_name'  => $userAddress['name'],
                'phone'       => $userAddress['phone'],
                'address'     => $userAddress['address'],
                'postal_code' => $userAddress['postal_code']
            ];
        } elseif($post['type'] == 'Pickup Order') {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $user['name'],
                    'phone'       => $user['phone']
                ],
            ];
        } elseif($post['type'] == 'GO-SEND'){
            //check key GO-SEND
            $checkKey = Gosend::checkKey();
            if(isset($checkKey) && $checkKey['status'] == 'fail'){
                DB::rollback();
                return response()->json($checkKey);
            }

            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $user['name'],
                    'phone'       => $user['phone']
                ],
            ];
            $dataShipping = [
                'name'        => $post['destination']['name'],
                'phone'       => $user['destination']['phone'],
                'address'     => $post['destination']['address']
            ];
        }

        if (!isset($post['latitude'])) {
            $post['latitude'] = null;
        }

        if (!isset($post['longitude'])) {
            $post['longitude'] = null;
        }

        if (!isset($post['notes'])) {
            $post['notes'] = null;
        }

        $type = $post['type'];
        if($post['type'] == 'GO-SEND'){
            $type = 'Pickup Order';
        }

        DB::beginTransaction();
        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'transaction_date'            => $post['transaction_date'],
            'transaction_receipt_number'  => 'TRX-'.app($this->setting_trx)->getrandomnumber(15).'-'.date('YmdHis'),
            'trasaction_type'             => $type,
            'transaction_notes'           => $post['notes'],
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_shipment'        => $post['shipping'],
            'transaction_is_free'         => $isFree,
            'transaction_service'         => $post['service'],
            'transaction_discount'        => $post['discount'],
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $post['grandTotal'],
            'transaction_point_earned'    => $post['point'],
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $post['payment_type'],
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'membership_level'            => $post['membership_level'],
            'membership_promo_id'         => $post['membership_promo_id'],
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'void_date'                   => null,
        ];

        $insertTransaction = Transaction::create($transaction);
        // return $insertTransaction;
        if (!$insertTransaction) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }

        // Fraud Detection
        if($post['transaction_payment_status'] == 'Completed'){
            //update count transaction
            $updateCountTrx = User::where('id', $user['id'])->update(['count_transaction_day' => $user['count_transaction_day'] + 1, 'count_transaction_week' => $user['count_transaction_week'] + 1]);
            if (!$updateCountTrx) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Update User Count Transaction Failed']
                ]);
            }
    
            $userData = User::find($user['id']);
            
            //cek fraud detection transaction per day
            $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 day%')->first();
            if($fraudTrxDay && $fraudTrxDay['parameter_detail'] != null){
                if($userData['count_transaction_day'] >= $fraudTrxDay['parameter_detail']){
                    //send fraud detection to admin
                    $sendFraud = app($this->setting_fraud)->SendFraudDetection($fraudTrxDay['id_fraud_setting'], $userData, $insertTransaction['id_transaction'], null);
                }
            }
    
            //cek fraud detection transaction per week (last 7 days)
            $fraudTrxDay = FraudSetting::where('parameter', 'LIKE', '%transactions in 1 week%')->first();
            if($fraudTrxDay && $fraudTrxDay['parameter_detail'] != null){
                if($userData['count_transaction_day'] >= $fraudTrxDay['parameter_detail']){
                    //send fraud detection to admin
                    $sendFraud = app($this->setting_fraud)->SendFraudDetection($fraudTrxDay['id_fraud_setting'], $userData, $insertTransaction['id_transaction'], $lastDeviceId = null);
                }
            }
        }

        foreach ($post['item'] as $keyProduct => $valueProduct) {
            $checkProduct = Product::where('id_product', $valueProduct['id_product'])->with('category')->first();
            if (empty($checkProduct)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Product Not Found']
                ]);
            }

            $checkPriceProduct = ProductPrice::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
            if (empty($checkPriceProduct)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Product Price Not Valid']
                ]);
            }

            if(!isset($valueProduct['note'])){
                $valueProduct['note'] = null;
            }

            $dataProduct = [
                'id_transaction'               => $insertTransaction['id_transaction'],
                'id_product'                   => $checkProduct['id_product'],
                'id_outlet'                    => $insertTransaction['id_outlet'],
                'id_user'                      => $insertTransaction['id_user'],
                'transaction_product_qty'      => $valueProduct['qty'],
                'transaction_product_price'    => $checkPriceProduct['product_price_base'],
                'transaction_product_subtotal' => $valueProduct['qty'] * $checkPriceProduct['product_price_base'],
                'transaction_product_note'     => $valueProduct['note'],
                'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])).' '.date('H:i:s'),
                'updated_at'                   => date('Y-m-d H:i:s')
            ];

            $dataProductMidtrans = [
                'id'       => $checkProduct['id_product'],
                'price'    => $checkPriceProduct['product_price_base'],
                'name'     => $checkProduct['product_name'],
                'quantity' => $valueProduct['qty'],
            ];


            array_push($dataInsertProduct, $dataProduct);
            array_push($productMidtrans, $dataProductMidtrans);
            $totalWeight += $checkProduct['product_weight'] * $valueProduct['qty'];

            $dataUserTrxProduct = [
                'id_user'       => $insertTransaction['id_user'],
                'id_product'    => $checkProduct['id_product'],
                'product_qty'   => $valueProduct['qty'],
                'last_trx_date' => $insertTransaction['transaction_date']
            ];
            array_push($userTrxProduct, $dataUserTrxProduct);
        }

        array_push($dataDetailProduct, $productMidtrans);

        $dataShip = [
            'id'       => null,
            'price'    => $post['shipping'],
            'name'     => 'Shipping',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataShip);

        $dataService = [
            'id'       => null,
            'price'    => $post['service'],
            'name'     => 'Service',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataService);

        $dataTax = [
            'id'       => null,
            'price'    => $post['tax'],
            'name'     => 'Tax',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataTax);

        $dataDis = [
            'id'       => null,
            'price'    => -$post['discount'],
            'name'     => 'Discount',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataDis);

        $insrtProduct = TransactionProduct::insert($dataInsertProduct);
        if (!$insrtProduct) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Product Transaction Failed']
            ]);
        }

        $insertUserTrxProduct = app($this->transaction)->insertUserTrxProduct($userTrxProduct);
        if ($insertUserTrxProduct == 'fail') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Product Transaction Failed']
            ]);
        }

        if (isset($post['receive_at'])) {
            $post['receive_at'] = date('Y-m-d H:i:s', strtotime($post['receive_at']));
        } else {
            $post['receive_at'] = null;
        } 

        if (isset($post['id_admin_outlet_receive'])) {
            $post['id_admin_outlet_receive'] = $post['id_admin_outlet_receive'];
        } else {
            $post['id_admin_outlet_receive'] = null;
        }

        $configAdminOutlet = Configs::where('config_name', 'admin outlet')->first();

        if($configAdminOutlet && $configAdminOutlet['is_active'] == '1'){

            if ($post['type'] == 'Delivery') {
                $configAdminOutlet = Configs::where('config_name', 'admin outlet delivery order')->first();
            }else{
                $configAdminOutlet = Configs::where('config_name', 'admin outlet pickup order')->first(); 
            }

            if($configAdminOutlet && $configAdminOutlet['is_active'] == '1'){
                $adminOutlet = UserOutlet::where('id_outlet', $insertTransaction['id_outlet'])->orderBy('id_user_outlet');
            }
        }


        if ($post['type'] == 'Delivery') {
            $link = '';
            if($configAdminOutlet && $configAdminOutlet['is_active'] == '1'){
                $totalAdmin = $adminOutlet->where('delivery', 1)->first();
                if (empty($totalAdmin)) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Admin outlet is empty']
                    ]);
                }

                $link = env('APP_URL').'/transaction/admin/'.$insertTransaction['transaction_receipt_number'].'/'.$totalAdmin['phone'];
            }

            $order_id = MyHelper::createrandom(4, 'Besar Angka');
            
            //cek unique order id today
            $cekOrderId = TransactionShipment::join('transactions', 'transactions.id_transaction', 'transaction_shipments.id_transaction')
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            while($cekOrderId){
                $order_id = MyHelper::createrandom(4, 'Besar Angka');

                $cekOrderId = TransactionShipment::join('transactions', 'transactions.id_transaction', 'transaction_shipments.id_transaction')
                                                ->where('order_id', $order_id)
                                                ->whereDate('transaction_date', date('Y-m-d'))
                                                ->first();
            }

            if (isset($post['send_at'])) {
                $post['send_at'] = date('Y-m-d H:i:s', strtotime($post['send_at']));
            } else {
                $post['send_at'] = null;
            }

            if (isset($post['id_admin_outlet_send'])) {
                $post['id_admin_outlet_send'] = $post['id_admin_outlet_send'];
            } else {
                $post['id_admin_outlet_send'] = null;
            }

            $dataShipment = [
                'id_transaction'           => $insertTransaction['id_transaction'],
                'order_id'                 => $order_id,
                'depart_name'              => $outlet['outlet_name'],
                'depart_phone'             => $outlet['outlet_phone'],
                'depart_address'           => $outlet['outlet_address'],
                'depart_id_city'           => $outlet['id_city'],
                'destination_name'         => $userAddress['name'],
                'destination_phone'        => $userAddress['phone'],
                'destination_address'      => $userAddress['address'],
                'destination_id_city'      => $userAddress['id_city'],
                'destination_description'  => $userAddress['description'],
                'shipment_total_weight'    => $totalWeight,
                'shipment_courier'         => $post['courier'],
                'shipment_courier_service' => $post['cour_service'],
                'shipment_courier_etd'     => $post['cour_etd'],
                'receive_at'               => $post['receive_at'],
                'id_admin_outlet_receive'  => $post['id_admin_outlet_receive'],
                'send_at'                  => $post['send_at'],
                'id_admin_outlet_send'     => $post['id_admin_outlet_send'],
                'short_link'               => $link
            ];

            $insertShipment = TransactionShipment::create($dataShipment);
            if (!$insertShipment) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Shipment Transaction Failed']
                ]);
            }
        } elseif ($post['type'] == 'Pickup Order' || $post['type'] == 'GO-SEND') {
            $link = '';
            if($configAdminOutlet && $configAdminOutlet['is_active'] == '1'){
                $totalAdmin = $adminOutlet->where('pickup_order', 1)->first();
                if (empty($totalAdmin)) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Admin outlet is empty']
                    ]);
                }

                $link = env('APP_URL').'/transaction/admin/'.$insertTransaction['transaction_receipt_number'].'/'.$totalAdmin['phone'];
            }
            $order_id = MyHelper::createrandom(4, 'Besar Angka');

            //cek unique order id today
            $cekOrderId = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            while($cekOrderId){
                $order_id = MyHelper::createrandom(4, 'Besar Angka');

                $cekOrderId = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                                ->where('order_id', $order_id)
                                                ->whereDate('transaction_date', date('Y-m-d'))
                                                ->first();
            }

            if (isset($post['taken_at'])) {
                $post['taken_at'] = date('Y-m-d H:i:s', strtotime($post['taken_at']));
            } else {
                $post['taken_at'] = null;
            }

            if (isset($post['id_admin_outlet_taken'])) {
                $post['id_admin_outlet_taken'] = $post['id_admin_outlet_taken'];
            } else {
                $post['id_admin_outlet_taken'] = null;
            }

            if(isset($post['pickup_type'])){
                $pickupType = $post['pickup_type'];
            }elseif($post['type'] == 'GO-SEND'){
                $pickupType = 'right now';
            }else{
                $pickupType = 'set time';
            }

            if($pickupType == 'set time'){

                if (date('Y-m-d H:i:s', strtotime($post['pickup_at'])) <= date('Y-m-d H:i:s')) {
                    $settingTime = Setting::where('key', 'processing_time')->first();

                    $pickup = date('Y-m-d H:i:s', strtotime('+ '.$settingTime['value'].'minutes'));
                } else {
                    $pickup = date('Y-m-d H:i:s', strtotime($post['pickup_at']));
                }
            }else{
                $pickup = null;
            }

            $dataPickup = [
                'id_transaction'          => $insertTransaction['id_transaction'],
                'order_id'                => $order_id,
                'short_link'              => env('APP_URL').'/transaction/'.$order_id.'/status',
                'pickup_type'             => $pickupType,
                'pickup_at'               => $pickup,
                'receive_at'              => $post['receive_at'],
                'taken_at'                => $post['taken_at'],
                'id_admin_outlet_receive' => $post['id_admin_outlet_receive'],
                'id_admin_outlet_taken'   => $post['id_admin_outlet_taken'],
                'short_link'              => $link
            ];

            if($post['type'] == 'GO-SEND'){
                $dataPickup['pickup_by'] = 'GO-SEND';
            }else{
                $dataPickup['pickup_by'] = 'Customer';
            }

            $insertPickup = TransactionPickup::create($dataPickup);
           
            if (!$insertPickup) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Pickup Order Transaction Failed']
                ]);
            }

            //insert pickup go-send
            if($post['type'] == 'GO-SEND'){
                $dataGoSend['id_transaction_pickup'] = $insertPickup['id_transaction_pickup'];
                $dataGoSend['origin_name']           = $outlet['outlet_name'];
                $dataGoSend['origin_phone']          = $outlet['outlet_phone'];
                $dataGoSend['origin_address']        = $outlet['outlet_address'];
                $dataGoSend['origin_latitude']       = $outlet['outlet_latitude'];
                $dataGoSend['origin_longitude']      = $outlet['outlet_longitude'];
                $dataGoSend['origin_note']           = '';
                $dataGoSend['destination_name']      = $post['destination']['name'];
                $dataGoSend['destination_phone']     = $post['destination']['phone'];
                $dataGoSend['destination_address']   = $post['destination']['address'];
                $dataGoSend['destination_latitude']  = $post['destination']['latitude'];
                $dataGoSend['destination_longitude'] = $post['destination']['longitude'];

                if(isset($post['destination_note'])){
                    $dataGoSend['destination_note'] = $post['destination']['note'];
                }

                $gosend = TransactionPickupGoSend::create($dataGoSend);
                if (!$gosend) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Transaction GO-SEND Failed']
                    ]);
                }

                $id_pickup_go_send = $gosend->id_transaction_pickup_go_send;
            }
        }

        if ($post['transaction_payment_status'] == 'Completed') {
            if (!empty($user['memberships'][0]['membership_name'])) {
                $level = $user['memberships'][0]['membership_name'];
                $percentageP = $user['memberships'][0]['benefit_point_multiplier'] / 100;
                $percentageB = $user['memberships'][0]['benefit_cashback_multiplier'] / 100;
            } else {
                $level = null;
                $percentageP = 0;
                $percentageB = 0;
            }

            if ($insertTransaction['transaction_point_earned'] != 0) {
                $settingPoint = Setting::where('key', 'point_conversion_value')->first();

                $dataLog = [
                    'id_user'                     => $insertTransaction['id_user'],
                    'point'                       => $insertTransaction['transaction_point_earned'] * $percentageP,
                    'id_reference'                => $insertTransaction['id_transaction'],
                    'source'                      => 'Transaction',
                    'grand_total'                 => $insertTransaction['transaction_grandtotal'],
                    'point_conversion'            => $settingPoint['value'],
                    'membership_level'            => $level,
                    'membership_point_percentage' => $percentageP * 100
                ];

                $insertDataLog = LogPoint::create($dataLog);
                if (!$insertDataLog) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Point Failed']
                    ]);
                }
            }

            if ($insertTransaction['transaction_cashback_earned'] != 0) {
                $settingCashback = Setting::where('key', 'cashback_conversion_value')->first();

                $dataLogCash = [
                    'id_user'                        => $insertTransaction['id_user'],
                    'balance'                        => $insertTransaction['transaction_cashback_earned'] * $percentageB,
                    'id_reference'                   => $insertTransaction['id_transaction'],
                    'source'                         => 'Transaction',
                    'grand_total'                    => $insertTransaction['transaction_grandtotal'],
                    'ccashback_conversion'           => $settingCashback['value'],
                    'membership_level'               => $level,
                    'membership_cashback_percentage' => $percentageB * 100
                ];

                $insertDataLogCash = LogBalance::create($dataLogCash);
                if (!$insertDataLogCash) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Cashback Failed']
                    ]);
                }
            }

            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
        }

        if (isset($post['payment_type'])) {

            if ($post['payment_type'] == 'Balance') {
                $save = app($this->balance)->topUp($insertTransaction['id_user'], $insertTransaction['transaction_grandtotal'], $insertTransaction['id_transaction']);

                if (!isset($save['status'])) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Transaction failed']]);
                }

                if ($save['status'] == 'fail') {
                    DB::rollback();
                    return response()->json($save);
                }

                if ($save['type'] == 'no_topup') {
                    $mid['order_id'] = $insertTransaction['transaction_receipt_number'];

                    $insertTransaction = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('transaction_receipt_number', $insertTransaction['transaction_receipt_number'])->first();

                    $send = app($this->notif)->notification($mid, $insertTransaction);

                    if (!$send) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Transaction failed']
                        ]);
                    }
                }
            }

            // if ($post['payment_type'] == 'Midtrans') {
            //     if ($post['transaction_payment_status'] == 'Completed') {
            //         //bank
            //         $bank = ['BNI', 'Mandiri', 'BCA'];
            //         $getBank = array_rand($bank);

            //         //payment_method
            //         $method = ['credit_card', 'bank_transfer', 'direct_debit'];
            //         $getMethod = array_rand($method);

            //         $dataInsertMidtrans = [
            //             'id_transaction'     => $insertTransaction['id_transaction'],
            //             'approval_code'      => 000000,
            //             'bank'               => $bank[$getBank],
            //             'eci'                => $this->getrandomnumber(2),
            //             'transaction_time'   => $insertTransaction['transaction_date'].' 22:00:00',
            //             'gross_amount'       => $insertTransaction['transaction_grandtotal'],
            //             'order_id'           => $insertTransaction['transaction_receipt_number'],
            //             'payment_type'       => $method[$getMethod],
            //             'signature_key'      => $this->getrandomstring(),
            //             'status_code'        => 200,
            //             'vt_transaction_id'  => $this->getrandomstring(8).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(12),
            //             'transaction_status' => 'capture',
            //             'fraud_status'       => 'accept',
            //             'status_message'     => 'Veritrans payment notification'
            //         ];

            //         $insertDataMidtrans = TransactionPaymentMidtran::create($dataInsertMidtrans);
            //         if (!$insertDataMidtrans) {
            //             DB::rollback();
            //             return response()->json([
            //                 'status'    => 'fail',
            //                 'messages'  => ['Insert Data Midtrans Failed']
            //             ]);
            //         }

            //         DB::commit();
            //         return ['status' => 'success'];
            //     } else {
            //         DB::commit();
            //         return response()->json([
            //             'status' => 'success',
            //             'result' => $insertTransaction
            //         ]);
            //     }
            // } elseif ($post['payment_type'] == 'Manual') {
            //     DB::commit();
            //     return response()->json([
            //         'status' => 'success',
            //         'result' => $insertTransaction
            //     ]);

            // } else {
            //     $save = app($this->balance)->topUp($insertTransaction['id_user'], $insertTransaction['transaction_grandtotal'], $insertTransaction['id_transaction']);

            //     if ($save['status'] == 'fail') {
            //         DB::rollback();
            //         return response()->json($save);
            //     }

            //     if ($save['status'] == 'success') {
            //         if ($save['type'] == 'no_topup') {
            //             $mid['order_id'] = $insertTransaction['transaction_receipt_number'];

            //             $newTrx = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('transaction_receipt_number', $insertTransaction['transaction_receipt_number'])->first();
            //             $send = app($this->notif)->notification($mid, $newTrx);

            //             DB::commit();
            //             return response()->json([
            //                 'status' => 'success',
            //                 'result' => $insertTransaction
            //             ]);
            //         } else {
            //             DB::commit();
            //             return response()->json([
            //                 'status' => 'success',
            //                 'type'   => 'topup',
            //                 'result' => $insertTransaction
            //             ]);
            //         }
            //     }
            // }
        } 
        // else {
            DB::commit();
            return response()->json([
                'status' => 'success',
                'result' => $insertTransaction
            ]);
        // }
        
    }

    public function sendStatus($url, $method, $data=null) {
        $client = new Client;

        if ($method == 'GET') {
            $content = array(
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode('SB-Mid-server-ode5bp0rUKf87v7VX-hQvFX1:'),
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json'
                ]
            );
        } else {
            $content = array(
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode('SB-Mid-server-ode5bp0rUKf87v7VX-hQvFX1:'),
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json'
                ],
                'json' => (array) $data
            );
        }
  
        try {
            $response =  $client->request($method, $url, $content);
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
