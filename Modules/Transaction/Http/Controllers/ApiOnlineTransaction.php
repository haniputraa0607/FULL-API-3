<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\DailyTransactions;
use App\Jobs\DisburseJob;
use App\Jobs\FraudJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductCategory;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandProduct;
use App\Http\Models\ProductModifier;
use App\Http\Models\User;
use App\Http\Models\UserAddress;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionProductModifier;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionAdvanceOrder;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\UserOutlet;
use App\Http\Models\TransactionSetting;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\Configs;
use App\Http\Models\Holiday;
use App\Http\Models\OutletToken;
use App\Http\Models\UserLocationDetail;
use App\Http\Models\Deal;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\DealsUser;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferral;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\UserPromo;
use Modules\Subscription\Entities\TransactionPaymentSubscription;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\PromoCampaign\Entities\PromoCampaignReport;

use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\PromoCampaign\Lib\PromoCampaignTools;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request as RequestGuzzle;
use Guzzle\Http\Message\Response as ResponseGuzzle;
use Guzzle\Http\Exception\ServerErrorResponseException;

use Modules\UserFeedback\Entities\UserFeedbackLog;

use DB;
use DateTime;
use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\GoSend;
use App\Lib\PushNotificationHelper;

use Modules\Transaction\Http\Requests\Transaction\NewTransaction;
use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;
use Modules\Transaction\Http\Requests\CheckTransaction;

class ApiOnlineTransaction extends Controller
{
    public $saveImage = "img/payment/manual/";

    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->notif         = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo       = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->plastic       = "Modules\Plastic\Http\Controllers\PlasticController";
    }

    public function newTransaction(NewTransaction $request) {
        $post = $request->json()->all();
        // return $post;
        $totalPrice = 0;
        $totalWeight = 0;
        $totalDiscount = 0;
        $grandTotal = app($this->setting_trx)->grandTotal();
        $order_id = null;
        $id_pickup_go_send = null;
        $promo_code_ref = null;

        if (isset($post['headers'])) {
            unset($post['headers']);
        }
        if($post['type'] == 'Advance Order'){
            $post['id_outlet'] = Setting::where('key','default_outlet')->pluck('value')->first();
        }
        $dataInsertProduct = [];
        $productMidtrans = [];
        $dataDetailProduct = [];
        $userTrxProduct = [];

        if(isset($post['id_outlet'])){
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->first();
            if (empty($outlet)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet Not Found']
                    ]);
            }
        }else{
            $outlet = optional();
        }

        if($post['type'] != 'Pickup Order' && !$outlet->delivery_order) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Maaf, Outlet ini tidak support untuk delivery order']
                ]);
        }

        $issetDate = false;
        if (isset($post['transaction_date'])) {
            $issetDate = true;
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        //cek outlet active
        if(isset($outlet['outlet_status']) && $outlet['outlet_status'] == 'Inactive'){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet tutup']
            ]);
        }

        //cek outlet holiday
        if($issetDate == false){
            $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                    ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
            if(count($holiday) > 0){
                foreach($holiday as $i => $holi){
                    if($holi['yearly'] == '0'){
                        if($holi['date'] == date('Y-m-d')){
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Outlet tutup']
                            ]);
                        }
                    }else{
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet tutup']
                        ]);
                    }
                }
            }

            if($outlet['today']['is_closed'] == '1'){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet tutup']
                ]);
            }

             if($outlet['today']['close'] && $outlet['today']['open']){

                $settingTime = Setting::where('key', 'processing_time')->first();
                if($settingTime && $settingTime->value){
                    if($outlet['today']['close'] && date('H:i') > date('H:i', strtotime('-'.$settingTime->value.' minutes' ,strtotime($outlet['today']['close'])))){
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet tutup']
                        ]);
                    }
                }

                //cek outlet open - close hour
                if(($outlet['today']['open'] && date('H:i') < date('H:i', strtotime($outlet['today']['open']))) || ($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close'])))){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Outlet tutup']
                    ]);
                }
            }
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

        //suspend
        if(isset($user['is_suspended']) && $user['is_suspended'] == '1'){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di hello@example.id']
            ]);
        }

        $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->first()->is_active;

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

        $totalDisProduct = 0;

        // $productDis = $this->countDis($post);
        $productDis = app($this->setting_trx)->discountProduct($post);
        if ($productDis) {
            $totalDisProduct = $productDis;
        }

        // return $totalDiscount;

        // remove bonus item
        $pct = new PromoCampaignTools();
        $post['item'] = $pct->removeBonusItem($post['item']);

        // check promo code and referral
        $promo_error=[];
        $use_referral = false;
        $discount_promo = [];
        $promo_discount = 0;
        $promo_source = null;

        if($request->json('promo_code') || $request->json('id_deals_user') || $request->json('id_subscription_user')){
        	$removePromo = UserPromo::where('id_user',$request->user()->id)->delete();
        }

        if($request->json('promo_code') && !$request->json('id_deals_user')){
            $code=PromoCampaignPromoCode::where('promo_code',$request->promo_code)
                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                ->where( function($q){
                    $q->whereColumn('usage','<','limitation_usage')
                        ->orWhere('code_type','Single')
                        ->orWhere('limitation_usage',0);
                } )
                ->first();
            if ($code)
            {
                $post['id_promo_campaign_promo_code'] = $code->id_promo_campaign_promo_code;
                if($code->promo_type == "Referral"){
                    $promo_code_ref = $request->json('promo_code');
                    $use_referral = true;
                }

                $validate_user=$pct->validateUser($code->id_promo_campaign, $request->user()->id, $request->user()->phone, $request->device_type, $request->device_id, $errore,$code->id_promo_campaign_promo_code);

                $discount_promo=$pct->validatePromo($code->id_promo_campaign, $request->id_outlet, $post['item'], $errors);

                if ( !empty($errore) || !empty($errors)) {
                    DB::rollback();
                    return [
                        'status'=>'fail',
                        'messages'=>['Promo code not valid']
                    ];
                }

                $promo_source = 'promo_code';
                $promo_discount=$discount_promo['discount'];
            }
            else
            {
                return [
                    'status'=>'fail',
                    'messages'=>['Promo code not valid']
                ];
            }
        }
        elseif($request->json('id_deals_user') && !$request->json('promo_code'))
        {
        	$deals = app($this->promo_campaign)->checkVoucher($request->id_deals_user, 1);

			if($deals)
			{
				$discount_promo=$pct->validatePromo($deals->dealVoucher->id_deals, $request->id_outlet, $post['item'], $errors, 'deals');

				if ( !empty($errors) ) {
					DB::rollback();
                    return [
                        'status'=>'fail',
                        'messages'=>['Voucher is not valid']
                    ];
	            }

	            $promo_source = 'voucher_online';
	            $promo_discount=$discount_promo['discount'];
	        }
	        else
	        {
	        	return [
                    'status'=>'fail',
                    'messages'=>['Voucher is not valid']
                ];
	        }
        }
        elseif($request->json('id_deals_user') && $request->json('promo_code'))
        {
        	return [
                'status'=>'fail',
                'messages'=>['Promo is not valid']
            ];
        }

        $error_msg=[];

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
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
                $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                $mes = ['Data Not Valid'];

                if (isset($post['dis']->original['messages'])) {
                    $mes = $post['dis']->original['messages'];

                    if ($post['dis']->original['messages'] == ['Price Product Not Found']) {
                        if (isset($post['dis']->original['product'])) {
                            $mes = ['Price Product Not Found with product '.$post['dis']->original['product'].' at outlet '.$outlet['outlet_name']];
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['discount'] = $post['dis'] + $totalDisProduct;
            }elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                    if (isset($post['tax']->original['messages'])) {
                        $mes = $post['tax']->original['messages'];

                        if ($post['tax']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => $mes
                        ]);
                    }
            }
            else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        $post['point'] = app($this->setting_trx)->countTransaction('point', $post);
        $post['cashback'] = app($this->setting_trx)->countTransaction('cashback', $post);

        //count some trx user
        $countUserTrx = Transaction::where('id_user', $id)->where('transaction_payment_status', 'Completed')->count();

        $countSettingCashback = TransactionSetting::get();

        // return $countSettingCashback;
        if ($countUserTrx < count($countSettingCashback)) {
            // return $countUserTrx;
            $post['cashback'] = $post['cashback'] * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;

            if ($post['cashback'] > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                $post['cashback'] = $countSettingCashback[$countUserTrx]['cashback_maximum'];
            }
        } else {

            $maxCash = Setting::where('key', 'cashback_maximum')->first();

            if (count($user['memberships']) > 0) {
                $post['point'] = $post['point'] * ($user['memberships'][0]['benefit_point_multiplier']) / 100;
                $post['cashback'] = $post['cashback'] * ($user['memberships'][0]['benefit_cashback_multiplier']) / 100;

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

        if ($request->json('promo_code') || $request->json('id_deals_user') || $request->json('id_subscription_user')) {
        	if ($request->json('id_subscription_user')) {
        		$promo_source = 'subscription';
        	}
        	$check = $this->checkPromoGetPoint($promo_source);
        	if ( $check == 0 ) {
        		$post['cashback'] = 0;
            	$post['point']    = 0;
        	}
        }

        // apply cashback
        if ($use_referral){
            $referral_rule = PromoCampaignReferral::where('id_promo_campaign',$code->id_promo_campaign)->first();
            if(!$referral_rule){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Referrer Cashback Failed']
                ]);
            }
            $referred_cashback = 0;
            if($referral_rule->referred_promo_type == 'Cashback'){
                if($referral_rule->referred_promo_unit == 'Percent'){
                    $referred_discount_percent = $referral_rule->referred_promo_value<=100?$referral_rule->referred_promo_value:100;
                    $referred_cashback = $post['subtotal']*$referred_discount_percent/100;
                }else{
                    if($post['subtotal'] >= $referral_rule->referred_min_value){
                        $referred_cashback = $referral_rule->referred_promo_value<=$post['subtotal']?$referral_rule->referred_promo_value:$post['subtotal'];
                    }
                }
            }
            $post['cashback'] = $referred_cashback;
        }

        $detailPayment = [
            'subtotal' => $post['subtotal'],
            'shipping' => $post['shipping'],
            'tax'      => $post['tax'],
            'service'  => $post['service'],
            'discount' => $post['discount'],
        ];

        // return $detailPayment;
        $post['grandTotal'] = (int)$post['subtotal'] + (int)$post['discount'] + (int)$post['service'] + (int)$post['tax'] + (int)$post['shipping'];
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
            $dataAddress = $post['destination'];
            $dataAddress['latitude'] = number_format($dataAddress['latitude'],8);
            $dataAddress['longitude'] = number_format($dataAddress['longitude'],8);
            if($dataAddress['id_user_address']??false){
                $dataAddressKeys = ['id_user_address'=>$dataAddress['id_user_address']];
            }else{
                $dataAddressKeys = [
                    'latitude' => number_format($dataAddress['latitude'],8),
                    'longitude' => number_format($dataAddress['longitude'],8)
                ];
            }
            $dataAddressKeys['id_user'] = $user['id'];
            $addressx = UserAddress::where($dataAddressKeys)->first();
            if(!$addressx){
                $addressx = UserAddress::create($dataAddressKeys+$dataAddress);
            }elseif(!$addressx->favorite){
                $addressx->update($dataAddress);
            }
            $checkKey = GoSend::checkKey();
            if(is_array($checkKey) && $checkKey['status'] == 'fail'){
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
                'name'        => $user['name'],
                'phone'       => $user['phone'],
                'address'     => $post['destination']['address']
            ];
        }

        if (!isset($post['latitude'])) {
            $post['latitude'] = null;
        }

        if (!isset($post['longitude'])) {
            $post['longitude'] = null;
        }

        $distance = NULL;
        if(isset($post['latitude']) &&  isset($post['longitude'])){
            $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K");
        }

        if (!isset($post['notes'])) {
            $post['notes'] = null;
        }

        $type = $post['type'];
        $isFree = '0';
        $shippingGoSend = 0;

        if($post['type'] == 'GO-SEND'){
            if(!($outlet['outlet_latitude']&&$outlet['outlet_longitude']&&$outlet['outlet_phone']&&$outlet['outlet_address'])){
                app($this->outlet)->sendNotifIncompleteOutlet($outlet['id_outlet']);
                $outlet->notify_admin = 1;
                $outlet->save();
                return [
                    'status' => 'fail',
                    'messages' => ['Tidak dapat melakukan pengiriman dari outlet ini']
                ];
            }
            $coor_origin = [
                'latitude' => number_format($outlet['outlet_latitude'],8),
                'longitude' => number_format($outlet['outlet_longitude'],8)
            ];
            $coor_destination = [
                'latitude' => number_format($post['destination']['latitude'],8),
                'longitude' => number_format($post['destination']['longitude'],8)
            ];
            $type = 'Pickup Order';
            $shippingGoSendx = GoSend::getPrice($coor_origin,$coor_destination);
            $shippingGoSend = $shippingGoSendx[GoSend::getShipmentMethod()]['price']['total_price']??null;
            if($shippingGoSend === null){
                return [
                    'status' => 'fail',
                    'messages' => array_column($shippingGoSendx[GoSend::getShipmentMethod()]['errors']??[],'message')?:['Gagal menghitung ongkos kirim']
                ];
            }
            //cek free delivery
            // if($post['is_free'] == 'yes'){
            //     $isFree = '1';
            // }
            $isFree = 0;
        }

        DB::beginTransaction();
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();
        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'id_promo_campaign_promo_code'           => $post['id_promo_campaign_promo_code']??null,
            'transaction_date'            => $post['transaction_date'],
            // 'transaction_receipt_number'  => 'TRX-'.app($this->setting_trx)->getrandomnumber(8).'-'.date('YmdHis'),
            'trasaction_type'             => $type,
            'transaction_notes'           => $post['notes'],
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_shipment'        => $post['shipping'],
            'transaction_shipment_go_send'=> $shippingGoSend,
            'transaction_is_free'         => $isFree,
            'transaction_service'         => $post['service'],
            'transaction_discount'        => $post['discount'],
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $post['grandTotal'] + $shippingGoSend,
            'transaction_point_earned'    => $post['point'],
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $post['payment_type'],
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'membership_level'            => $post['membership_level'],
            'membership_promo_id'         => $post['membership_promo_id'],
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'distance_customer'           => $distance,
            'void_date'                   => null,
        ];

        if($transaction['transaction_grandtotal'] == 0){
            $transaction['transaction_payment_status'] = 'Completed';
        }

        $newTopupController = new NewTopupController();
        $checkHashBefore = $newTopupController->checkHash('log_balances', $id);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Your previous transaction data is invalid']
            ]);
        }

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($useragent,'iOS')) $useragent = 'IOS';
        elseif(stristr($useragent,'okhttp')) $useragent = 'Android';
        else $useragent = null;

        if($useragent){
            $transaction['transaction_device_type'] = $useragent;
        }

        $insertTransaction = Transaction::create($transaction);

        if (!$insertTransaction) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }
        // add report referral
        if($use_referral){
            $addPromoCounter = PromoCampaignReferralTransaction::create([
                'id_promo_campaign_promo_code' =>$code->id_promo_campaign_promo_code,
                'id_user' => $insertTransaction['id_user'],
                'id_referrer' => UserReferralCode::select('id_user')->where('id_promo_campaign_promo_code',$code->id_promo_campaign_promo_code)->pluck('id_user')->first(),
                'id_transaction' => $insertTransaction['id_transaction'],
                'referred_bonus_type' => $promo_discount?'Product Discount':'Cashback',
                'referred_bonus' => $promo_discount?:$insertTransaction['transaction_cashback_earned']
            ]);
            if(!$addPromoCounter){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Failed']
                ]);
            }

            $promo_code_ref = $request->promo_code;
        }

        // add transaction voucher
        if($request->json('id_deals_user')){
        	$update_voucher = DealsUser::where('id_deals_user','=',$request->id_deals_user)->update(['used_at' => date('Y-m-d H:i:s'), 'is_used' => 0]);
        	$update_deals = Deal::where('id_deals','=',$deals->dealVoucher['deals']['id_deals'])->update(['deals_total_used' => $deals->dealVoucher['deals']['deals_total_used']+1]);
            $addTransactionVoucher = TransactionVoucher::create([
                'id_deals_voucher' => $deals['id_deals_voucher'],
                'id_user' => $insertTransaction['id_user'],
                'id_transaction' => $insertTransaction['id_transaction']
            ]);
            if(!$addTransactionVoucher){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Failed']
                ]);
            }
        }

        // add payment subscription
        if ( $request->json('id_subscription_user') )
        {
        	$subscription_total = app($this->subscription_use)->calculate($request->id_subscription_user, $insertTransaction['transaction_subtotal'], $insertTransaction['transaction_subtotal'], $post['item'], $post['id_outlet'], $subs_error, $errorProduct, $subs_product, $subs_applied_product);

	        if (!empty($subs_error)) {
	        	DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Failed']
                ]);
	        }

	        $subscription['grandtotal'] = $insertTransaction['transaction_grandtotal'] - $subscription_total;
	        $data_subs = app($this->subscription_use)->checkSubscription( $request->json('id_subscription_user') );
	        $insert_subs_data['id_transaction'] = $insertTransaction['id_transaction'];
	        $insert_subs_data['id_subscription_user_voucher'] = $data_subs->id_subscription_user_voucher;
	        $insert_subs_data['subscription_nominal'] = $subscription_total;

	        $insert_subs_trx = TransactionPaymentSubscription::create($insert_subs_data);
	        $update_subs_voucher = SubscriptionUserVoucher::where('id_subscription_user_voucher','=',$data_subs->id_subscription_user_voucher)
	        						->update([
	        							'used_at' => date('Y-m-d H:i:s'),
	        							'id_transaction' => $insertTransaction['id_transaction']
	        						]);
			if (!$insert_subs_trx || !$update_subs_voucher) {
	        	DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Failed']
                ]);
            }

            //update when total = 0
            if(($transaction['transaction_grandtotal'] - $subscription_total) == 0){
                $updateTrx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
                    'transaction_payment_status' => 'Completed'
                ]);
                $insertTransaction['transaction_payment_status'] = 'Completed'; 
                $insertTransaction['transaction_grandtotal'] = 0;
            }
        }

        // add promo campaign report
        if($request->json('promo_code'))
        {
        	$promo_campaign_report = app($this->promo_campaign)->addReport(
				$code->id_promo_campaign,
				$code->id_promo_campaign_promo_code,
				$insertTransaction['id_transaction'],
				$insertTransaction['id_outlet'],
				$request->device_id?:'',
				$request->device_type?:''
			);

        	if (!$promo_campaign_report) {
        		DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Failed']
                ]);
        	}
        }

        //update receipt
        $receipt = 'J+'.MyHelper::createrandom(4,'Angka').time().substr($insertTransaction['id_outlet'], 0, 4);
        $updateReceiptNumber = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
            'transaction_receipt_number' => $receipt
        ]);

        if (!$updateReceiptNumber) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }

        MyHelper::updateFlagTransactionOnline($insertTransaction, 'pending', $user);

        $insertTransaction['transaction_receipt_number'] = $receipt;

        foreach (($discount_promo['item']??$post['item']) as $keyProduct => $valueProduct) {

            $this_discount=$valueProduct['discount']??0;

            $checkProduct = Product::where('id_product', $valueProduct['id_product'])->first();
            if (empty($checkProduct)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Product Not Found']
                ]);
            }

            $checkDetailProduct = ProductDetail::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
            if (!empty($checkDetailProduct) && $checkDetailProduct['product_detail_stock_status'] == 'Sold Out') {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Product '.$checkProduct['product_name'].' sudah habis, silakan pilih yang lain']
                ]);
            }

            if(!isset($valueProduct['note'])){
                $valueProduct['note'] = null;
            }

            $productPrice = 0;

            if($outlet['outlet_different_price']){
                $checkPriceProduct = ProductSpecialPrice::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
                if(!isset($checkPriceProduct['product_special_price'])){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Product Price Not Valid']
                    ]);
                }
                $productPrice = $checkPriceProduct['product_special_price'];
            }else{
                $checkPriceProduct = ProductGlobalPrice::where(['id_product' => $checkProduct['id_product']])->first();

                if(isset($checkPriceProduct['product_global_price'])){
                    $productPrice = $checkPriceProduct['product_global_price'];
                }else{
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Product Price Not Valid']
                    ]);
                }
            }

            $dataProduct = [
                'id_transaction'               => $insertTransaction['id_transaction'],
                'id_product'                   => $checkProduct['id_product'],
                'id_brand'                     => $valueProduct['id_brand'],
                'id_outlet'                    => $insertTransaction['id_outlet'],
                'id_user'                      => $insertTransaction['id_user'],
                'transaction_product_qty'      => $valueProduct['qty'],
                'transaction_product_price'    => $productPrice,
                'transaction_product_price_base' => NULL,
                'transaction_product_price_tax'  => NULL,
                'transaction_product_discount'   => $this_discount,
                'transaction_product_base_discount' => $valueProduct['base_discount'] ?? 0,
                'transaction_product_qty_discount'  => $valueProduct['qty_discount'] ?? 0,
                // remove discount from subtotal
                // 'transaction_product_subtotal' => ($valueProduct['qty'] * $checkPriceProduct['product_price'])-$this_discount,
                'transaction_product_subtotal' => ($valueProduct['qty'] * $productPrice),
                'transaction_product_note'     => $valueProduct['note'],
                'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])).' '.date('H:i:s'),
                'updated_at'                   => date('Y-m-d H:i:s')
            ];

            $trx_product = TransactionProduct::create($dataProduct);
            if (!$trx_product) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Product Transaction Failed']
                ]);
            }
            if(strtotime($insertTransaction['transaction_date'])){
                $trx_product->created_at = strtotime($insertTransaction['transaction_date']);
            }
            // array_push($dataInsertProduct, $dataProduct);

            $insert_modifier = [];
            $mod_subtotal = 0;
            $more_mid_text = '';
            foreach ($valueProduct['modifiers'] as $modifier) {
                $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                $mod = ProductModifier::select('product_modifiers.id_product_modifier','code','text','product_modifier_stock_status','product_modifier_price')
                    // product visible
                    ->leftJoin('product_modifier_details', function($join) use ($post) {
                        $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                            ->where('product_modifier_details.id_outlet',$post['id_outlet']);
                    })
                    ->where(function($query){
                        $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
                    })
                    ->where(function($q){
                        $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
                    })
                    ->where(function($q){
                        $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
                    })
                    ->groupBy('product_modifiers.id_product_modifier');
                if($outlet['outlet_different_price']){
                    $mod->join('product_modifier_prices',function($join) use ($post){
                        $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                        $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
                    });
                }else{
                    $mod->join('product_modifier_global_prices',function($join){
                        $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                    });
                }
                $mod = $mod->find($id_product_modifier);
                if(!$mod){
                    return [
                        'status' => 'fail',
                        'messages' => ['Modifier not found']
                    ];
                }
                $mod = $mod->toArray();
                $insert_modifier[] = [
                    'id_transaction_product'=>$trx_product['id_transaction_product'],
                    'id_transaction'=>$insertTransaction['id_transaction'],
                    'id_product'=>$checkProduct['id_product'],
                    'id_product_modifier'=>$id_product_modifier,
                    'id_outlet'=>$insertTransaction['id_outlet'],
                    'id_user'=>$insertTransaction['id_user'],
                    'type'=>$mod['type']??'',
                    'code'=>$mod['code']??'',
                    'text'=>$mod['text']??'',
                    'qty'=>$qty_product_modifier,
                    'transaction_product_modifier_price'=>$mod['product_modifier_price']*$qty_product_modifier,
                    'datetime'=>$insertTransaction['transaction_date']??date(),
                    'trx_type'=>$type,
                    // 'sales_type'=>'',
                    'created_at'                   => date('Y-m-d H:i:s'),
                    'updated_at'                   => date('Y-m-d H:i:s')
                ];
                $mod_subtotal += $mod['product_modifier_price']*$qty_product_modifier;
                if($qty_product_modifier>1){
                    $more_mid_text .= ','.$qty_product_modifier.'x '.$mod['text'];
                }else{
                    $more_mid_text .= ','.$mod['text'];
                }
            }
            $trx_modifier = TransactionProductModifier::insert($insert_modifier);
            if (!$trx_modifier) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Product Modifier Transaction Failed']
                ]);
            }
            $trx_product->transaction_modifier_subtotal = $mod_subtotal;
            $trx_product->transaction_product_subtotal += $trx_product->transaction_modifier_subtotal * $valueProduct['qty'];
            $trx_product->save();
            $dataProductMidtrans = [
                'id'       => $checkProduct['id_product'],
                'price'    => $productPrice + $mod_subtotal - ($trx_product['transaction_product_discount']/$trx_product['transaction_product_qty']),
                // 'name'     => $checkProduct['product_name'].($more_mid_text?'('.trim($more_mid_text,',').')':''), // name & modifier too long
                'name'     => $checkProduct['product_name'],
                'quantity' => $valueProduct['qty'],
            ];
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

        // $insrtProduct = TransactionProduct::insert($dataInsertProduct);
        // if (!$insrtProduct) {
        //     DB::rollback();
        //     return response()->json([
        //         'status'    => 'fail',
        //         'messages'  => ['Insert Product Transaction Failed']
        //     ]);
        // }
        $insertUserTrxProduct = app($this->transaction)->insertUserTrxProduct($userTrxProduct);
        if ($insertUserTrxProduct == 'fail') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Product Transaction Failed']
            ]);
        }

        if (isset($post['receive_at']) && $post['receive_at']) {
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


        //sum balance
        $sumBalance = LogBalance::where('id_user', $id)->sum('balance');
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

                $link = config('url.app_url').'/transaction/admin/'.$insertTransaction['transaction_receipt_number'].'/'.$totalAdmin['phone'];
            }

            $order_id = MyHelper::createrandom(4, 'Besar Angka');

            //cek unique order id uniq today and outlet
            $cekOrderId = TransactionShipment::join('transactions', 'transactions.id_transaction', 'transaction_shipments.id_transaction')
                                            ->where('id_outlet', $insertTransaction['id_outlet'])
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            while ($cekOrderId) {
                $order_id = MyHelper::createrandom(4, 'Besar Angka');

                $cekOrderId = TransactionShipment::join('transactions', 'transactions.id_transaction', 'transaction_shipments.id_transaction')
                                                ->where('id_outlet', $insertTransaction['id_outlet'])
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

                $link = config('url.app_url').'/transaction/admin/'.$insertTransaction['transaction_receipt_number'].'/'.$totalAdmin['phone'];
            }
            $order_id = MyHelper::createrandom(4, 'Besar Angka');

            //cek unique order id today
            $cekOrderId = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                            ->where('id_outlet', $insertTransaction['id_outlet'])
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            while($cekOrderId){
                $order_id = MyHelper::createrandom(4, 'Besar Angka');

                $cekOrderId = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                                ->where('id_outlet', $insertTransaction['id_outlet'])
                                                ->where('order_id', $order_id)
                                                ->whereDate('transaction_date', date('Y-m-d'))
                                                ->first();
            }

            //cek unique order id today
            $cekOrderId = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                            ->where('id_outlet', $insertTransaction['id_outlet'])
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            while ($cekOrderId) {
                $order_id = MyHelper::createrandom(4, 'Besar Angka');

                $cekOrderId = TransactionPickup::join('transactions', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                                ->where('id_outlet', $insertTransaction['id_outlet'])
                                                ->where('order_id', $order_id)
                                                ->whereDate('transaction_date', date('Y-m-d'))
                                                ->first();
            }

            if (isset($post['taken_at']) && $post['taken_at']) {
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
                $settingTime = Setting::where('key', 'processing_time')->first();
                if (date('Y-m-d H:i:s', strtotime($post['pickup_at'])) <= date('Y-m-d H:i:s', strtotime('- '.$settingTime['value'].'minutes'))) {
                    $pickup = date('Y-m-d H:i:s', strtotime('+ '.$settingTime['value'].'minutes'));
                }
                else {
                    if(isset($outlet['today']['close'])){
                        if(date('Y-m-d H:i', strtotime($post['pickup_at'])) > date('Y-m-d').' '.date('H:i', strtotime($outlet['today']['close']))){
                            $pickup =  date('Y-m-d').' '.date('H:i:s', strtotime($outlet['today']['close']));
                        }else{
                            $pickup = date('Y-m-d H:i:s', strtotime($post['pickup_at']));
                        }
                    }else{
                        $pickup = date('Y-m-d H:i:s', strtotime($post['pickup_at']));
                    }
                }
            }else{
                $pickup = null;
            }

            $dataPickup = [
                'id_transaction'          => $insertTransaction['id_transaction'],
                'order_id'                => $order_id,
                'short_link'              => config('url.app_url').'/transaction/'.$order_id.'/status',
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
            if($dataPickup['taken_at']){
                Transaction::where('id_transaction',$dataPickup['id_transaction'])->update(['show_rate_popup'=>1]);
            }
            //insert pickup go-send
            if($post['type'] == 'GO-SEND'){
                $dataGoSend['id_transaction_pickup'] = $insertPickup['id_transaction_pickup'];
                $dataGoSend['origin_name']           = $outlet['outlet_name'];
                $dataGoSend['origin_phone']          = $outlet['outlet_phone'];
                $dataGoSend['origin_address']        = $outlet['outlet_address'];
                $dataGoSend['origin_latitude']       = $outlet['outlet_latitude'];
                $dataGoSend['origin_longitude']      = $outlet['outlet_longitude'];
                $dataGoSend['origin_note']           = "NOTE: mohon hubungi penerima terlebih dahulu untuk informasi \nPickup Code $order_id";
                $dataGoSend['destination_name']      = $user['name'];
                $dataGoSend['destination_phone']     = $user['phone'];
                $dataGoSend['destination_address']   = $post['destination']['address'];
                $dataGoSend['destination_short_address'] = $post['destination']['short_address'];
                $dataGoSend['destination_address_name']   = $addressx->name;
                $dataGoSend['destination_latitude']  = $post['destination']['latitude'];
                $dataGoSend['destination_longitude'] = $post['destination']['longitude'];

                if(isset($post['destination']['description'])){
                    $dataGoSend['destination_note'] = $post['destination']['description'];
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
        } elseif ($post['type'] == 'Advance Order') {
            $order_id = MyHelper::createrandom(4, 'Besar Angka');
            //cek unique order id today
            $cekOrderId = TransactionAdvanceOrder::join('transactions', 'transactions.id_transaction', 'transaction_advance_orders.id_transaction')
                                            ->where('id_outlet', $insertTransaction['id_outlet'])
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            while($cekOrderId){
                $order_id = MyHelper::createrandom(4, 'Besar Angka');

                $cekOrderId = TransactionAdvanceOrder::join('transactions', 'transactions.id_transaction', 'transaction_advance_orders.id_transaction')
                                            ->where('id_outlet', $insertTransaction['id_outlet'])
                                            ->where('order_id', $order_id)
                                            ->whereDate('transaction_date', date('Y-m-d'))
                                            ->first();
            }

            $dataAO = [
                'id_transaction' => $insertTransaction['id_transaction'],
                'order_id' => $order_id,
                'address' => $post['address'],
                'receive_at' => $post['receive_at'],
                'receiver_name' => $post['receiver_name'],
                'receiver_phone' => $post['receiver_phone'],
                'date_delivery' => $post['date_delivery']
            ];
            $insertAO = TransactionAdvanceOrder::create($dataAO);
            if (!$insertAO) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Advance Order Failed']
                ]);
            }
        }

        if ($post['transaction_payment_status'] == 'Completed') {
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
            if (!$checkMembership) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Recount membership failed']
                ]);
            }
        }

        if (isset($post['payment_type']) || $insertTransaction['transaction_grandtotal'] == 0) {

            if ($post['payment_type'] == 'Balance' || $insertTransaction['transaction_grandtotal'] == 0) {

                if($insertTransaction['transaction_grandtotal'] > 0){
                    $save = app($this->balance)->topUp($insertTransaction['id_user'], ($subscription['grandtotal']??$insertTransaction['transaction_grandtotal']), $insertTransaction['id_transaction']);
    
                    if (!isset($save['status'])) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Transaction failed']]);
                    }
    
                    if ($save['status'] == 'fail') {
                        DB::rollback();
                        return response()->json($save);
                    }
                }else{
                    $save['status'] = 'success'; 
                    $save['type'] = 'no_topup';
                }

                if($save['status'] == 'success'){
                    $checkFraudPoint = app($this->setting_fraud)->fraudTrxPoint($sumBalance, $user, ['id_outlet' => $insertTransaction['id_outlet']]);
                    if(isset($checkFraudPoint['status'])){
                        return response()->json($checkFraudPoint);
                    }
                }
                
                if ($post['transaction_payment_status'] == 'Completed' || $save['type'] == 'no_topup') {

                    if($config_fraud_use_queue == 1){
                        FraudJob::dispatch($user, $insertTransaction, 'transaction')->onConnection('fraudqueue');
                    }else {
                        if($config_fraud_use_queue != 1){
                            $checkFraud = app($this->setting_fraud)->checkFraudTrxOnline($user, $insertTransaction);
                        }
                    }
                    //inset pickup_at when pickup_type = right now
                    if($insertPickup['pickup_type'] == 'right now'){
                        $settingTime = Setting::where('key', 'processing_time')->first();
                        $updatePickup = TransactionPickup::where('id_transaction', $insertTransaction['id_transaction'])->update(['pickup_at' => date('Y-m-d H:i:s', strtotime('+ '.$settingTime['value'].'minutes'))]);
                    }
                }

                if ($save['type'] == 'no_topup') {
                    $mid['order_id'] = $insertTransaction['transaction_receipt_number'];
                    $mid['gross_amount'] = 0;

                    $insertTransaction = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('transaction_receipt_number', $insertTransaction['transaction_receipt_number'])->first();

                    if($request->json('id_deals_user') && !$request->json('promo_code'))
			        {
			        	$check_trx_voucher = TransactionVoucher::where('id_deals_voucher', $deals['id_deals_voucher'])->where('status','success')->count();

						if(($check_trx_voucher??false) > 1)
						{
							DB::rollBack();
				            return [
				                'status'=>'fail',
				                'messages'=>['Voucher is not valid']
				            ];
				        }
			        }

                    if ($configAdminOutlet && $configAdminOutlet['is_active'] == '1') {
                        $sendAdmin = app($this->notif)->sendNotif($insertTransaction);
                        if (!$sendAdmin) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Transaction failed']
                            ]);
                        }
                    }

                    $send = app($this->notif)->notification($mid, $insertTransaction);

                    if (!$send) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Transaction failed']
                        ]);
                    }

                    if ($post['type'] == 'Pickup Order' || $post['type'] == 'GO-SEND') {
                        $orderIdSend = $insertPickup['order_id'];
                    } else {
                        $orderIdSend = $insertShipment['order_id'];
                    }

                    $sendNotifOutlet = $this->outletNotif($insertTransaction['id_transaction']);
                    // return $sendNotifOutlet;
                    $dataRedirect = $this->dataRedirect($insertTransaction['transaction_receipt_number'], 'trx', '1');

                    if($post['latitude'] && $post['longitude']){
                        $savelocation = $this->saveLocation($post['latitude'], $post['longitude'], $insertTransaction['id_user'], $insertTransaction['id_transaction'], $insertTransaction['id_outlet']);
                     }

                    // PromoCampaignTools::applyReferrerCashback($insertTransaction);

                    /* Add daily Trx*/
                    $dataDailyTrx = [
                        'id_transaction'    => $insertTransaction['id_transaction'],
                        'id_outlet'         => $outlet['id_outlet'],
                        'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
                        'referral_code_use_date'=> date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
                        'id_user'           => $user['id'],
                        'referral_code'     => NULL
                    ];
                    $createDailyTrx = DailyTransactions::create($dataDailyTrx);

                    /* Fraud Referral*/
                    if($promo_code_ref){
                        //======= Start Check Fraud Referral User =======//
                        $data = [
                            'id_user' => $insertTransaction['id_user'],
                            'referral_code' => $promo_code_ref,
                            'referral_code_use_date' => $insertTransaction['transaction_date'],
                            'id_transaction' => $insertTransaction['id_transaction']
                        ];
                        if($config_fraud_use_queue == 1){
                            FraudJob::dispatch($user, $data, 'referral user')->onConnection('fraudqueue');
                            FraudJob::dispatch($user, $data, 'referral')->onConnection('fraudqueue');
                        }else{
                            app($this->setting_fraud)->fraudCheckReferralUser($data);
                            app($this->setting_fraud)->fraudCheckReferral($data);
                        }
                        //======= End Check Fraud Referral User =======//
                    }

                    DB::commit();
                    //insert to disburse job for calculation income outlet
                    DisburseJob::dispatch(['id_transaction' => $insertTransaction['id_transaction']])->onConnection('disbursequeue');

                    //remove for result
                    unset($insertTransaction['user']);
                    unset($insertTransaction['outlet']);
                    unset($insertTransaction['product_transaction']);

                    return response()->json([
                        'status'     => 'success',
                        'redirect'   => false,
                        'result'     => $insertTransaction,
                        'additional' => $dataRedirect
                    ]);
                }
            }

            if ($post['payment_type'] == 'Midtrans') {
                if ($post['transaction_payment_status'] == 'Completed') {
                    //bank
                    $bank = ['BNI', 'Mandiri', 'BCA'];
                    $getBank = array_rand($bank);

                    //payment_method
                    $method = ['credit_card', 'bank_transfer', 'direct_debit'];
                    $getMethod = array_rand($method);

                    $dataInsertMidtrans = [
                        'id_transaction'     => $insertTransaction['id_transaction'],
                        'approval_code'      => 000000,
                        'bank'               => $bank[$getBank],
                        'eci'                => $this->getrandomnumber(2),
                        'transaction_time'   => $insertTransaction['transaction_date'],
                        'gross_amount'       => $insertTransaction['transaction_grandtotal'],
                        'order_id'           => $insertTransaction['transaction_receipt_number'],
                        'payment_type'       => $method[$getMethod],
                        'signature_key'      => $this->getrandomstring(),
                        'status_code'        => 200,
                        'vt_transaction_id'  => $this->getrandomstring(8).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(12),
                        'transaction_status' => 'capture',
                        'fraud_status'       => 'accept',
                        'status_message'     => 'Veritrans payment notification'
                    ];

                    $insertDataMidtrans = TransactionPaymentMidtran::create($dataInsertMidtrans);
                    if (!$insertDataMidtrans) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Insert Data Midtrans Failed']
                        ]);
                    }

                    // if($post['latitude'] && $post['longitude']){
                    //     $savelocation = $this->saveLocation($post['latitude'], $post['longitude'], $insertTransaction['id_user'], $insertTransaction['id_transaction']);
                    // }

                }

            }
        }

        // if($post['latitude'] && $post['longitude']){
        //    $savelocation = $this->saveLocation($post['latitude'], $post['longitude'], $insertTransaction['id_user'], $insertTransaction['id_transaction']);
        // }

        /* Add daily Trx*/
        $dataDailyTrx = [
            'id_transaction'    => $insertTransaction['id_transaction'],
            'id_outlet'         => $outlet['id_outlet'],
            'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
            'referral_code_use_date'=> date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
            'id_user'           => $user['id'],
            'referral_code'     => NULL
        ];
        $createDailyTrx = DailyTransactions::create($dataDailyTrx);

        /* Fraud Referral*/
        if($promo_code_ref){
            //======= Start Check Fraud Referral User =======//
            $data = [
                'id_user' => $insertTransaction['id_user'],
                'referral_code' => $promo_code_ref,
                'referral_code_use_date' => $insertTransaction['transaction_date'],
                'id_transaction' => $insertTransaction['id_transaction']
            ];
            if($config_fraud_use_queue == 1){
                FraudJob::dispatch($user, $data, 'referral user')->onConnection('fraudqueue');
                FraudJob::dispatch($user, $data, 'referral')->onConnection('fraudqueue');
            }else{
                app($this->setting_fraud)->fraudCheckReferralUser($data);
                app($this->setting_fraud)->fraudCheckReferral($data);
            }
            //======= End Check Fraud Referral User =======//
        }

        if($request->json('id_deals_user') && !$request->json('promo_code'))
        {
        	$check_trx_voucher = TransactionVoucher::where('id_deals_voucher', $deals['id_deals_voucher'])->where('status','success')->count();

			if(($check_trx_voucher??false) > 1)
			{
				DB::rollBack();
	            return [
	                'status'=>'fail',
	                'messages'=>['Voucher is not valid']
	            ];
	        }
        }

        DB::commit();

        //insert to disburse job for calculation income outlet
        DisburseJob::dispatch(['id_transaction' => $insertTransaction['id_transaction']])->onConnection('disbursequeue');

        $insertTransaction['cancel_message'] = 'Are you sure you want to cancel this transaction?';
        return response()->json([
            'status'   => 'success',
            'redirect' => true,
            'result'   => $insertTransaction
        ]);

    }

    /**
     * Get info from given cart data
     * @param  CheckTransaction $request [description]
     * @return View                    [description]
     */
    public function checkTransaction(CheckTransaction $request) {
        $post = $request->json()->all();
        $post['item'] = $this->mergeProducts($post['item']);
        $grandTotal = app($this->setting_trx)->grandTotal();
        $user = $request->user();
        //Check Outlet
        $id_outlet = $post['id_outlet'];
        $outlet = Outlet::where('id_outlet', $id_outlet)->with('today')->first();
        if (empty($outlet)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
                ]);
        }

        $issetDate = false;
        if (isset($post['transaction_date'])) {
            $issetDate = true;
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }
        $outlet_status = 1;
        //cek outlet active
        if(isset($outlet['outlet_status']) && $outlet['outlet_status'] == 'Inactive'){
            // DB::rollback();
            // return response()->json([
            //     'status'    => 'fail',
            //     'messages'  => ['Outlet tutup']
            // ]);
            $outlet_status = 0;
        }

        //cek outlet holiday
        if($issetDate == false){
            $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                    ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
            if(count($holiday) > 0){
                foreach($holiday as $i => $holi){
                    if($holi['yearly'] == '0'){
                        if($holi['date'] == date('Y-m-d')){
                            // DB::rollback();
                            // return response()->json([
                            //     'status'    => 'fail',
                            //     'messages'  => ['Outlet tutup']
                            // ]);
                            $outlet_status = 0;
                        }
                    }else{
                        // DB::rollback();
                        // return response()->json([
                        //     'status'    => 'fail',
                        //     'messages'  => ['Outlet tutup']
                        // ]);
                        $outlet_status = 0;
                    }
                }
            }

            if($outlet['today']['is_closed'] == '1'){
                // DB::rollback();
                // return response()->json([
                //     'status'    => 'fail',
                //     'messages'  => ['Outlet tutup']
                // ]);
                $outlet_status = 0;
            }

             if($outlet['today']['close'] && $outlet['today']['close'] != "00:00" && $outlet['today']['open'] && $outlet['today']['open'] != '00:00'){

                $settingTime = Setting::where('key', 'processing_time')->first();
                if($settingTime && $settingTime->value){
                    if($outlet['today']['close'] && date('H:i') > date('H:i', strtotime('-'.$settingTime->value.' minutes' ,strtotime($outlet['today']['close'])))){
                        // DB::rollback();
                        // return response()->json([
                        //     'status'    => 'fail',
                        //     'messages'  => ['Outlet tutup']
                        // ]);
                        $outlet_status = 0;
                    }
                }

                //cek outlet open - close hour
                if(($outlet['today']['open'] && date('H:i') < date('H:i', strtotime($outlet['today']['open']))) || ($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close'])))){
                    // DB::rollback();
                    // return response()->json([
                    //     'status'    => 'fail',
                    //     'messages'  => ['Outlet tutup']
                    // ]);
                    $outlet_status = 0;
                }
            }
        }

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if (!isset($post['shipping'])) {
            $post['shipping'] = 0;
        }

        $shippingGoSend = 0;

        $error_msg=[];

        if($post['type'] != 'Pickup Order' && !$outlet->delivery_order) {
            $error_msg[] = 'Maaf, Outlet ini tidak support untuk delivery order';
        }

        if(($post['type']??null) == 'GO-SEND'){
            if(!($outlet['outlet_latitude']&&$outlet['outlet_longitude']&&$outlet['outlet_phone']&&$outlet['outlet_address'])){
                app($this->outlet)->sendNotifIncompleteOutlet($outlet['id_outlet']);
                $outlet->notify_admin = 1;
                $outlet->save();
                return [
                    'status' => 'fail',
                    'messages' => ['Tidak dapat melakukan pengiriman dari outlet ini']
                ];
            }
            $coor_origin = [
                'latitude' => number_format($outlet['outlet_latitude'],8),
                'longitude' => number_format($outlet['outlet_longitude'],8)
            ];
            $coor_destination = [
                'latitude' => number_format($post['destination']['latitude'],8),
                'longitude' => number_format($post['destination']['longitude'],8)
            ];
            $type = 'Pickup Order';
            $shippingGoSendx = GoSend::getPrice($coor_origin,$coor_destination);
            $shippingGoSend = $shippingGoSendx[GoSend::getShipmentMethod()]['price']['total_price']??null;
            if($shippingGoSend === null){
                $error_msg += array_column($shippingGoSendx[GoSend::getShipmentMethod()]['errors']??[],'message')?:['Gagal menghitung ongkos kirim'];
            }
            //cek free delivery
            // if($post['is_free'] == 'yes'){
            //     $isFree = '1';
            // }
            $isFree = 0;
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

        // hitung product discount
        $totalDisProduct = 0;
        $productDis = app($this->setting_trx)->discountProduct($post);
        if (is_numeric($productDis)) {
            $totalDisProduct = $productDis;
        }else{
            return $productDis;
        }

        // remove bonus item
        $pct = new PromoCampaignTools();
        $post['item'] = $pct->removeBonusItem($post['item']);

        // check promo code & voucher
        $promo_error=null;
        $promo_source = null;
        if($request->promo_code && !$request->id_subscription_user && !$request->id_deals_user){
        	$code = app($this->promo_campaign)->checkPromoCode($request->promo_code, 1, 1);

            if ($code)
            {
            	if ($code['promo_campaign']['date_end'] < date('Y-m-d H:i:s')) {
	        		$promo_error='Promo campaign is ended';
	        	}
	        	else
	        	{
		            $validate_user=$pct->validateUser($code->id_promo_campaign, $request->user()->id, $request->user()->phone, $request->device_type, $request->device_id, $errore,$code->id_promo_campaign_promo_code);

		            if ($validate_user) {
			            $discount_promo=$pct->validatePromo($code->id_promo_campaign, $request->id_outlet, $post['item'], $errors, 'promo_campaign', $errorProduct);

			            $promo_source = 'promo_code';
			            if ( !empty($errore) || !empty($errors) ) {
			            	$promo_error = app($this->promo_campaign)->promoError('transaction', $errore, $errors, $errorProduct);
			            	if ($errorProduct==1) {
				            	$promo_error['product_label'] = app($this->promo_campaign)->getProduct('promo_campaign', $code['promo_campaign'])['product']??'';
						        $promo_error['product'] = $pct->getRequiredProduct($code->id_promo_campaign)??null;
						    }
						    $promo_source = null;
			            }
			            $promo_discount=$discount_promo['discount'];
		            }
		            else
		            {
		                if(isset($errore)){
		            		foreach ($errore as $key => $value) {
		            			array_push($promo_error['message'], $value);
		            		}
		            	}
		            }
	        	}
            }
            else
            {
            	$error = ['Promo code invalid'];
            	$promo_error = app($this->promo_campaign)->promoError('transaction', $error);
            }
        }
        elseif(!$request->promo_code && !$request->id_subscription_user && $request->id_deals_user)
        {
        	$deals = app($this->promo_campaign)->checkVoucher($request->id_deals_user, 1, 1);

			if($deals)
			{
				$discount_promo=$pct->validatePromo($deals->dealVoucher->id_deals, $request->id_outlet, $post['item'], $errors, 'deals', $errorProduct);

				$promo_source = 'voucher_online';
				if ( !empty($errors) ) {
					$code = $deals->toArray();
	            	$promo_error = app($this->promo_campaign)->promoError('transaction', null, $errors, $errorProduct);
	            	if ($errorProduct==1) {
		            	$promo_error['product_label'] = app($this->promo_campaign)->getProduct('deals', $code['deal_voucher']['deals'])['product']??'';
			        	$promo_error['product'] = $pct->getRequiredProduct($deals->dealVoucher->id_deals, 'deals')??null;
	            	}
	            	$promo_source = null;
	            }
	            $promo_discount=$discount_promo['discount'];
	        }
	        else
	        {
	        	$error = ['Voucher is not valid'];
	        	$promo_error = app($this->promo_campaign)->promoError('transaction', $error);
	        }
        }
        // end check promo code & voucher

        $tree = [];
        // check and group product
        $subtotal = 0;
        $missing_product = 0;
        // return [$discount_promo['item'],$errors];
        $is_advance = 0;
        $global_max_order = Outlet::select('max_order')->where('id_outlet',$post['id_outlet'])->pluck('max_order')->first();
        if($global_max_order == null){
            $global_max_order = Setting::select('value')->where('key','max_order')->pluck('value')->first();
            if($global_max_order == null){
                $global_max_order = 100;
            }
        }
        foreach ($discount_promo['item']??$post['item'] as &$item) {
            // get detail product
            $product = Product::select([
                    'products.id_product','products.product_name','products.product_code','products.product_description',
                DB::raw('(CASE
                        WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = '.$post['id_outlet'].' ) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' )
                        ELSE product_global_price.product_global_price
                    END) as product_price'),
                    DB::raw('(CASE
                            WHEN (select product_detail.product_detail_stock_status from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' ) 
                            is NULL THEN "Available"
                            ELSE (select product_detail.product_detail_stock_status from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        END) as product_stock_status'),
                    DB::raw('(CASE
                            WHEN (select product_detail.max_order from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' ) 
                            is NULL THEN NULL
                            ELSE (select product_detail.max_order from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        END) as max_order'),
                    'brand_product.id_product_category','brand_product.id_brand',
                ])
                ->join('brand_product','brand_product.id_product','=','products.id_product')
                ->leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
                // brand produk ada di outlet
                ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
                ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    END)')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    END)')
                ->where(function ($query) use ($post){
                    $query->orWhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' ) is NOT NULL');
                    $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product) is NOT NULL');
                })
                ->with([
                    'brand_category' => function($query){
                        $query->groupBy('id_product','id_brand');
                    },
                    'photos' => function($query){
                        $query->select('id_product','product_photo');
                    },
                    'product_promo_categories' => function($query){
                        $query->select('product_promo_categories.id_product_promo_category','product_promo_category_name as product_category_name','product_promo_category_order as product_category_order');
                    },
                ])
            ->having('product_price','>',0)
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->find($item['id_product']);
            $max_order = $product['max_order'];
            if($max_order==null){
                $max_order = $global_max_order;
            }
            if($max_order&&($item['qty']>$max_order)){
                $is_advance = 1;
                $error_msg[] = MyHelper::simpleReplace(
                    Setting::select('value_text')->where('key','transaction_exceeds_limit_text')->pluck('value_text')->first()?:'Transaksi anda melebihi batas! Maksimal transaksi untuk %product_name% : %max_order%',
                    [
                        'product_name' => $product['product_name'],
                        'max_order' => $max_order
                    ]
                );
                continue;
            }
            if(!$product){
                $missing_product++;
                continue;
            }
            $product->append('photo');
            $product = $product->toArray();
            if($product['product_stock_status']!='Available'){
                $error_msg[] = MyHelper::simpleReplace(
                    '%product_name% is out of stock',
                    [
                        'product_name' => $product['product_name']
                    ]
                );
                continue;
            }
            unset($product['photos']);
            $product['id_custom'] = $item['id_custom']??null;
            $product['qty'] = $item['qty'];
            $product['note'] = $item['note']??'';
            $product['promo_discount'] = $item['discount']??0;
            isset($item['new_price']) ? $product['new_price']=$item['new_price'] : '';
            $product['is_promo'] = $item['is_promo']??0;
            $product['is_free'] = $item['is_free']??0;
            $product['bonus'] = $item['bonus']??0;
            // get modifier
            $mod_price = 0;
            $product['modifiers'] = [];
            $removed_modifier = [];
            $missing_modifier = 0;
            foreach ($item['modifiers'] as $key => $modifier) {
                $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                $mod = ProductModifier::select('product_modifiers.id_product_modifier','code','text','product_modifier_stock_status','product_modifier_price')
                    // product visible
                    ->leftJoin('product_modifier_details', function($join) use ($post) {
                        $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                            ->where('product_modifier_details.id_outlet',$post['id_outlet']);
                    })
                    ->where(function($query){
                        $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
                    })
                    ->where(function($q){
                        $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
                    })
                    ->where(function($q){
                        $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
                    })
                    ->groupBy('product_modifiers.id_product_modifier');
                if($outlet['outlet_different_price']){
                    $mod->join('product_modifier_prices',function($join) use ($post){
                        $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                        $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
                    });
                }else{
                    $mod->join('product_modifier_global_prices',function($join) use ($post){
                        $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                    });
                }
                $mod = $mod->find($id_product_modifier);
                if(!$mod){
                    $missing_modifier++;
                    continue;
                }
                $mod = $mod->toArray();
                $mod['qty'] = $qty_product_modifier;
                $mod['product_modifier_price'] = (int) $mod['product_modifier_price'];
                $product['modifiers'][]=$mod;
                $mod_price+=$mod['qty']*$mod['product_modifier_price'];
            }
            if($missing_modifier){
                $error_msg[] = MyHelper::simpleReplace(
                    '%missing_modifier% modifiers for product %product_name% not found',
                    [
                        'missing_modifier' => $missing_modifier,
                        'product_name' => $product['product_name']
                    ]
                );
            }
            if($removed_modifier){
                $error_msg[] = MyHelper::simpleReplace(
                    'Modifier %removed_modifier% for product %product_name% is out of stock',
                    [
                        'removed_modifier' => implode(',',$removed_modifier),
                        'product_name' => $product['product_name']
                    ]
                );
            }
            if(!isset($tree[$product['id_brand']]['name_brand'])){
                $tree[$product['id_brand']] = Brand::select('name_brand','id_brand')->find($product['id_brand'])->toArray();
            }
            $product['product_price_total'] = ($product['qty'] * ($product['product_price']+$mod_price));
            $product['product_price_raw'] = (int) $product['product_price'];
            $product['product_price_raw_total'] = (int) $product['product_price']+$mod_price;
            $product['product_price'] = MyHelper::requestNumber($product['product_price']+$mod_price, '_CURRENCY');
            $tree[$product['id_brand']]['products'][]=$product;
            $subtotal += $product['product_price_total'];
            // return $product;
        }
        // return $tree;
        if($missing_product){
            $error_msg[] = MyHelper::simpleReplace(
                '%missing_product% products not found',
                [
                    'missing_product' => $missing_product
                ]
            );
        }

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['subtotal'] = $subtotal - $totalDisProduct;
            } elseif ($valueTotal == 'discount') {
                // $post['dis'] = $this->countTransaction($valueTotal, $post);
                $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                if (isset($post['dis']->original['messages'])) {
                    $mes = $post['dis']->original['messages'];

                    if ($post['dis']->original['messages'] == ['Price Product Not Found']) {
                        if (isset($post['dis']->original['product'])) {
                            $mes = ['Price Product Not Found with product '.$post['dis']->original['product'].' at outlet '.$outlet['outlet_name']];
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['discount'] = $post['dis'] + $totalDisProduct;
            }elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                    if (isset($post['tax']->original['messages'])) {
                        $mes = $post['tax']->original['messages'];

                        if ($post['tax']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => $mes
                        ]);
                    }
            }
            else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }
        $outlet['today']['status'] = $outlet_status?'open':'closed';

        $post['discount'] = $post['discount'] + ($promo_discount??0);

        $result['outlet'] = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_address' => $outlet['outlet_address'],
            'delivery_order' => $outlet['delivery_order'],
            'today' => $outlet['today']
        ];
        $result['item'] = array_values($tree);
        $result['is_advance_order'] = $is_advance;
        $result['subtotal'] = $subtotal;
        $result['shipping'] = $post['shipping']+$shippingGoSend;
        $result['discount'] = $post['discount'];
        $result['service'] = (int) $post['service'];
        $result['tax'] = (int) $post['tax'];
        $result['grandtotal'] = (int)$post['subtotal'] + (int)(-$post['discount']) + (int)$post['service'] + (int)$post['tax'] + (int)$post['shipping'] + $shippingGoSend;
        $result['subscription'] = 0;
        $result['used_point'] = 0;
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;
        $result['total_promo'] = app($this->promo)->availablePromo();
        if ($request->id_subscription_user && !$request->promo_code && !$request->id_deals_user)
        {
        	$promo_source = 'subscription';
	        $result['subscription'] = app($this->subscription_use)->calculate($request->id_subscription_user, $result['subtotal'], $result['subtotal'], $post['item'], $post['id_outlet'], $subs_error, $errorProduct, $subs_product, $subs_applied_product);
	        if (!empty($subs_error)) {
	        	$error = $subs_error;
	        	$promo_error = app($this->promo_campaign)->promoError('transaction', $error, null, $errorProduct);
	        	$promo_error['product'] = $subs_applied_product??null;
	        	$promo_error['product_label'] = $subs_product??'';
	        }
        }
        $result['get_point'] = ($post['payment_type'] != 'Balance') ? $this->checkPromoGetPoint($promo_source) : 0;
        if (isset($post['payment_type'])&&$post['payment_type'] == 'Balance') {
            if($balance>=$result['grandtotal']){
                $result['used_point'] = $result['grandtotal'];

	            if ($result['subscription'] >= $result['used_point']) {
	            	$result['used_point'] = 0;
	            }else{
	            	$result['used_point'] = $result['used_point'] - $result['subscription'];
	            }
            }else{
                $result['used_point'] = $balance;
            }


            $result['points'] -= $result['used_point'];
        }

        if (!empty($result['subscription'])) 
        {
	        if ($result['subscription'] >= $result['grandtotal']) {
	        	$result['grandtotal'] = 0;
	        }else{
	        	$result['grandtotal'] = $result['grandtotal'] - $result['subscription'];
	        }
        }

        $result['total_payment'] = $result['grandtotal'] - $result['used_point'];
        return MyHelper::checkGet($result)+['messages'=>$error_msg,'promo_error'=>$promo_error];
    }

    public function saveLocation($latitude, $longitude, $id_user, $id_transaction, $id_outlet){

        $cek = UserLocationDetail::where('id_reference', $id_transaction)->where('activity', 'Transaction')->first();
        if($cek){
            return true;
        }

        $googlemap = MyHelper::get(env('GEOCODE_URL').$latitude.','.$longitude.'&key='.env('GEOCODE_KEY'));

        if(isset($googlemap['results'][0]['address_components'])){

            $street = null;
            $route = null;
            $level1 = null;
            $level2 = null;
            $level3 = null;
            $level4 = null;
            $level5 = null;
            $country = null;
            $postal = null;
            $address = null;

            foreach($googlemap['results'][0]['address_components'] as $data){
                if($data['types'][0] == 'postal_code'){
                    $postal = $data['long_name'];
                }
                elseif($data['types'][0] == 'route'){
                    $route = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_5'){
                    $level5 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_4'){
                    $level4 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_3'){
                    $level3 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_2'){
                    $level2 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_1'){
                    $level1 = $data['long_name'];
                }
                elseif($data['types'][0] == 'country'){
                    $country = $data['long_name'];
                }
            }

            if($googlemap['results'][0]['formatted_address']){
                $address = $googlemap['results'][0]['formatted_address'];
            }

            $outletCode = null;
            $outletName = null;

            $outlet = Outlet::find($id_outlet);
            if($outlet){
                $outletCode = $outlet['outlet_code'];
                $outletCode = $outlet['outlet_name'];
            }

            $logactivity = UserLocationDetail::create([
                'id_user' => $id_user,
                'id_reference' => $id_transaction,
                'id_outlet' => $id_outlet,
                'outlet_code' => $outletCode,
                'outlet_name' => $outletName,
                'activity' => 'Transaction',
                'action' => 'Completed',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'response_json' => json_encode($googlemap),
                'route' => $route,
                'street_address' => $street,
                'administrative_area_level_5' => $level5,
                'administrative_area_level_4' => $level4,
                'administrative_area_level_3' => $level3,
                'administrative_area_level_2' => $level2,
                'administrative_area_level_1' => $level1,
                'country' => $country,
                'postal_code' => $postal,
                'formatted_address' => $address
            ]);

            if($logactivity) {
                return true;
            }
        }

        return false;
    }

    public function dataRedirect($id, $type, $success)
    {
        $button = '';

        $list = Transaction::where('transaction_receipt_number', $id)->first();
        if (empty($list)) {
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        $dataEncode = [
            'transaction_receipt_number'   => $id,
            'type' => $type,
        ];

        if (isset($success)) {
            $dataEncode['trx_success'] = $success;
            $button = 'LIHAT NOTA';
        }

        $title = 'Sukses';
        if ($list['transaction_payment_status'] == 'Pending') {
            $title = 'Pending';
        }

        if ($list['transaction_payment_status'] == 'Terbayar') {
            $title = 'Terbayar';
        }

        if ($list['transaction_payment_status'] == 'Sukses') {
            $title = 'Sukses';
        }

        if ($list['transaction_payment_status'] == 'Gagal') {
            $title = 'Gagal';
        }

        $encode = json_encode($dataEncode);
        $base = base64_encode($encode);

        $send = [
            'button'                     => $button,
            'title'                      => $title,
            'payment_status'             => $list['transaction_payment_status'],
            'transaction_receipt_number' => $list['transaction_receipt_number'],
            'transaction_grandtotal'     => $list['transaction_grandtotal'],
            'type'                       => $type,
            'url'                        => env('VIEW_URL').'/transaction/web/view/detail?data='.$base
        ];

        return $send;
    }

    public function outletNotif($id_trx)
    {
        $trx = Transaction::where('id_transaction', $id_trx)->first();
        if ($trx['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $id_trx)->first();
        } else {
            $detail = TransactionShipment::where('id_transaction', $id_trx)->first();
        }

        $dataProduct = TransactionProduct::where('id_transaction', $id_trx)->with('product')->get();

        $count = count($dataProduct);
        $stringBody = "";
        $totalSemua = 0;

        foreach ($dataProduct as $key => $value) {
            $totalSemua += $value['transaction_product_qty'];
            $stringBody .= $value['product']['product_name']." - ".$value['transaction_product_qty']." pcs \n";
        }

        // return $stringBody;

        $outletToken = OutletToken::where('id_outlet', $trx['id_outlet'])->get();

        if (isset($detail['pickup_by'])) {
            if ($detail['pickup_by'] == 'Customer') {
                $type = 'Pickup';
                if(isset($detail['pickup_at'])){
                    $type = $type.' ('.date('H:i', strtotime($detail['pickup_at'])).' )';
                }
            }else{
                $type = 'Delivery';
            }

        } else {
            $type = 'Delivery';
        }

        $user = User::where('id', $trx['id_user'])->first();
        if (!empty($outletToken)) {
            if(env('PUSH_NOTIF_OUTLET') == 'fcm'){
                $tokens = $outletToken->pluck('token')->toArray();
                if(!empty($tokens)){
                    $subject = $type.' - Rp. '.number_format($trx['transaction_grandtotal'], 0, ',', '.').' - '.$totalSemua.' pcs - '.$detail['order_id'].' - '.$user['name'];
                    $dataPush = ['type' => 'trx', 'id_reference'=> $id_trx];
                    if ($detail['pickup_type'] == 'set time') {
                        $replacer = [
                            ['%name%', '%receipt_number%', '%order_id%'],
                            [$user->name, $trx->receipt_number, $detail['order_id']],
                        ];
                        $setting_msg = json_decode(MyHelper::setting('transaction_set_time_notif_message_outlet','value_text'), true);
                        $dataPush += [
                            'push_notif_local' => 1,
                            'title_5mnt'       => str_replace($replacer[0], $replacer[1], $setting_msg['title_5mnt'] ?? 'Pesanan %order_id% akan diambil 5 menit lagi'),
                            'msg_5mnt'         => str_replace($replacer[0], $replacer[1], $setting_msg['msg_5mnt'] ?? 'Pesanan %order_id% atas nama %name% akan diambil 5 menit lagi nih, segera disiapkan ya !'),
                            'title_15mnt'       => str_replace($replacer[0], $replacer[1], $setting_msg['title_5mnt'] ?? 'Pesanan %order_id% akan diambil 15 menit lagi'),
                            'msg_15mnt'         => str_replace($replacer[0], $replacer[1], $setting_msg['msg_5mnt'] ?? 'Pesanan %order_id% atas nama %name% akan diambil 15 menit lagi nih, segera disiapkan ya !'),
                            'pickup_time'       => $detail->pickup_at,
                        ];
                    } else {
                        $dataPush += [
                            'push_notif_local' => 0
                        ];                        
                    }
                    $push = PushNotificationHelper::sendPush($tokens, $subject, $stringBody, null, $dataPush);
                }
            }else{
                $dataArraySend = [];

                foreach ($outletToken as $key => $value) {
                    $dataOutletSend = [
                        'to'    => $value['token'],
                        'title' => $type.' - Rp. '.number_format($trx['transaction_grandtotal'], 0, ',', '.').' - '.$totalSemua.' pcs - '.$detail['order_id'].' - '.$user['name'].'',
                        'body'  => $stringBody,
                        'data'  => ['order_id' => $detail['order_id']]
                    ];
                    if ($detail['pickup_type'] == 'set time') {
                        $replacer = [
                            ['%name%', '%receipt_number%', '%order_id%'],
                            [$user->name, $trx->receipt_number, $detail['order_id']],
                        ];
                        $setting_msg = json_decode(MyHelper::setting('transaction_set_time_notif_message_outlet','value_text'), true);
                        $dataOutletSend += [
                            'push_notif_local' => 1,
                            'title_5mnt'       => str_replace($replacer[0], $replacer[1], $setting_msg['title_5mnt'] ?? 'Pesanan %order_id% akan diambil 5 menit lagi'),
                            'msg_5mnt'         => str_replace($replacer[0], $replacer[1], $setting_msg['msg_5mnt'] ?? 'Pesanan %order_id% atas nama %name% akan diambil 5 menit lagi nih, segera disiapkan ya !'),
                            'title_15mnt'       => str_replace($replacer[0], $replacer[1], $setting_msg['title_5mnt'] ?? 'Pesanan %order_id% akan diambil 15 menit lagi'),
                            'msg_15mnt'         => str_replace($replacer[0], $replacer[1], $setting_msg['msg_5mnt'] ?? 'Pesanan %order_id% atas nama %name% akan diambil 15 menit lagi nih, segera disiapkan ya !'),
                            'pickup_time'       => $detail->pickup_at,
                        ];
                    }else {
                        $dataOutletSend += [
                            'push_notif_local' => 0
                        ];
                    }
                    array_push($dataArraySend, $dataOutletSend);

                }

                $curl = $this->sendStatus('https://exp.host/--/api/v2/push/send', 'POST', $dataArraySend);
                if (!$curl) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Transaction failed']
                    ]);
                }
            }
        }

        return true;
    }

    public function sendStatus($url, $method, $data=null) {
        $client = new Client;

        $content = array(
            'headers' => [
                'host'            => 'exp.host',
                'accept'          => 'application/json',
                'accept-encoding' => 'gzip, deflate',
                'content-type'    => 'application/json'
            ],
            'json' => (array) $data
        );

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

     public function getrandomstring($length = 120) {

       global $template;
       settype($template, "string");

       $template = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring;
    }

     public function getrandomnumber($length) {

       global $template;
       settype($template, "string");

       $template = "0987654321";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring;
    }

    public function checkPromoGetPoint($promo_source)
    {
    	if (empty($promo_source)) {
    		return 1;
    	}

    	if ($promo_source != 'promo_code' && $promo_source != 'voucher_online' && $promo_source != 'voucher_offline' && $promo_source != 'subscription') {
    		return 0;
    	}

    	$config = app($this->promo)->promoGetCashbackRule();
    	// $getData = Configs::whereIn('config_name',['promo code get point','voucher offline get point','voucher online get point'])->get()->toArray();

    	// foreach ($getData as $key => $value) {
    	// 	$config[$value['config_name']] = $value['is_active'];
    	// }

    	if ($promo_source == 'promo_code') {
    		if ($config['promo code get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	if ($promo_source == 'voucher_online') {
    		if ($config['voucher online get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	if ($promo_source == 'voucher_offline') {
    		if ($config['voucher offline get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	if ($promo_source == 'subscription') {
    		if ($config['subscription get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	return 0;
    }
    public function cancelTransaction(Request $request)
    {
        $id_transaction = $request->id;
        $trx = Transaction::where('id_transaction', $id_transaction)->first();
        if(!$trx || $trx->transaction_payment_status != 'Pending'){
            return MyHelper::checkGet([],'Transaction cannot be canceled');
        }
        $errors = '';

        $cancel = \Modules\IPay88\Lib\IPay88::create()->cancel('trx',$trx,$errors, $request->last_url);

        if($cancel){
            return ['status'=>'success'];
        }
        return [
            'status'=>'fail',
            'messages' => $errors?:['Something went wrong']
        ];
    }

    public function availablePayment(Request $request)
    {
        $availablePayment = config('payment_method');

        $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
        $payments = [];

        $config = [
            'credit_card_payment_gateway' => MyHelper::setting('credit_card_payment_gateway', 'value', 'Ipay88')
        ];
        $last_status = [];
        foreach ($setting as $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment || !($payment['status'] ?? false) || (!$request->show_all && !($value['status'] ?? false))) {
                unset($availablePayment[$value['code']]);
                continue;
            }
            if(!is_numeric($payment['status'])){
                $var = explode(':',$payment['status']);
                if(($config[$var[0]]??false) != ($var[1]??true)) {
                    $last_status[$var[0]] = $value['status'];
                    unset($availablePayment[$value['code']]);
                    continue;
                }
            }
            $payments[] = [
                'code'            => $value['code'],
                'payment_gateway' => $payment['payment_gateway'],
                'payment_method'  => $payment['payment_method'],
                'logo'            => $payment['logo'],
                'text'            => $payment['text'],
                'status'          => (int) $value['status'] ? 1 : 0
            ];
            unset($availablePayment[$value['code']]);
        }
        foreach ($availablePayment as $code => $payment) {
            $status = 0;
            if (!$payment['status'] || !is_numeric($payment['status'])) {
                $var = explode(':',$payment['status']);
                if(($config[$var[0]]??false) != ($var[1]??true)) {
                    continue;
                }
                $status = (int) ($last_status[$var[0]] ?? 0);
            }
            if($request->show_all || $status) {
                $payments[] = [
                    'code'            => $code,
                    'payment_gateway' => $payment['payment_gateway'],
                    'payment_method'  => $payment['payment_method'],
                    'logo'            => $payment['logo'],
                    'text'            => $payment['text'],
                    'status'          => $status
                ];
            }
        }
        return MyHelper::checkGet($payments);
    }
    /**
     * update available payment
     * @param
     * {
     *     payments: [
     *         {'code': 'xxx', status: 1}
     *     ]
     * }
     * @return [type]           [description]
     */
    public function availablePaymentUpdate(Request $request)
    {
        $availablePayment = config('payment_method');
        foreach ($request->payments as $key => $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment || !($payment['status'] ?? false)) {
                continue;
            }
            $payments[] = [
                'code'     => $value['code'],
                'status'   => $value['status'] ?? 0,
                'position' => $key + 1,
            ];
        }
        $update = Setting::updateOrCreate(['key' => 'active_payment_methods'], ['value_text' => json_encode($payments)]);
        return MyHelper::checkUpdate($update);
    }

    public function mergeProducts($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'bonus' => isset($item['bonus'])?$item['bonus']:'0',
                'id_brand' => $item['id_brand'],
                'id_product' => $item['id_product'],
                'note' => $item['note'],
                'modifiers' => array_map(function($i){
                        return [
                            'id_product_modifier' => $i['id_product_modifier'],
                            'qty' => $i['qty']
                        ];
                    },$item['modifiers']??[]),
            ];
            usort($new_item['modifiers'],function($a, $b) { return $a['id_product_modifier'] <=> $b['id_product_modifier']; });
            $pos = array_search($new_item, $new_items);
            if($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['qty'];
                $id_custom[] = $item['id_custom']??0;
            } else {
                $item_qtys[$pos] += $item['qty'];
            }
        }
        // update qty
        foreach ($new_items as $key => &$value) {
            $value['qty'] = $item_qtys[$key];
            $value['id_custom'] = $id_custom[$key];
        }

        return $new_items;
    }
}
