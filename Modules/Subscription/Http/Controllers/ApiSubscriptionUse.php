<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionContent;
use Modules\Subscription\Entities\SubscriptionContentDetail;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Deals\Entities\DealsContent;
use Modules\Deals\Entities\DealsContentDetail;
use App\Http\Models\Setting;

use Modules\Subscription\Http\Requests\ListSubscription;
use Modules\Subscription\Http\Requests\Step1Subscription;
use Modules\Subscription\Http\Requests\Step2Subscription;
use Modules\Subscription\Http\Requests\Step3Subscription;
use Modules\Subscription\Http\Requests\DetailSubscription;

use Modules\PromoCampaign\Lib\PromoCampaignTools;
use DB;
use Illuminate\Support\Facades\Auth;

class ApiSubscriptionUse extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
    }

    public function checkSubscription($id_subscription_user=null, $outlet=null, $product=null, $product_detail=null, $active=null, $id_subscription_user_voucher=null, $brand=null)
    {
    	if (!empty($id_subscription_user_voucher)) 
    	{
    		$subs = SubscriptionUserVoucher::where('id_subscription_user_voucher', '=', $id_subscription_user_voucher);
    	}
    	else
    	{
    		$subs = SubscriptionUserVoucher::where('subscription_users.id_subscription_user', '=', $id_subscription_user);
    	}

    	$subs = $subs->join( 'subscription_users', 'subscription_users.id_subscription_user', '=', 'subscription_user_vouchers.id_subscription_user' )
    			->whereIn('subscription_users.paid_status', ['Free', 'Completed'])
    			->whereNull('subscription_user_vouchers.used_at')
    			->where('id_user', Auth::id());

    	if (!empty($outlet)) {
    		$subs = $subs->with(
    			'subscription_user.subscription.outlets_active'
    		);
    	}

    	if (!empty($product)) {
    		$subs = $subs->with(
    			'subscription_user.subscription.subscription_products'
    		);
    	}

    	if (!empty($brand)) {
    		$subs = $subs->with(
    			'subscription_user.subscription.brand'
    		);
    	}

    	if (!empty($product_detail)) {
    		$subs = $subs->with([
    		    			'subscription_user.subscription.subscription_products.product' => function($q){
    		    				$q->select('id_product','id_product_category','product_code','product_name');
    		    			}
    		    		]);
    	}
    			
    	if (!empty($active)) {
    		$subs = $subs->where('subscription_users.subscription_expired_at','>=',date('Y-m-d H:i:s'))
		    			->where(function($q) {
		    				$q->where('subscription_users.subscription_active_at','<=',date('Y-m-d H:i:s'))	
		    					->orWhereNull('subscription_users.subscription_active_at');
		    			});
    	}

    	$subs = $subs->first();

    	return $subs;
    }

    public function calculate($request, $id_subscription_user, $grandtotal, $subtotal, $item, $id_outlet, &$errors, &$errorProduct=0, &$product="", &$applied_product="", $delivery_fee=0)
    {
    	if (empty($id_subscription_user)) {
    		return 0;
    	}


    	$subs = $this->checkSubscription($id_subscription_user, 1, 1, 1);

    	// check if subscription exists
    	if (!$subs) {
    		$errors[] = 'Subscription not valid';
    		return 0;
    	}

    	$subs_obj = $subs;
    	$subs = $subs->toArray();
    	$type = $subs['subscription_user']['subscription']['subscription_discount_type'];

    	// check expired date
        if ($subs['subscription_expired_at'] < date('Y-m-d H:i:s')) {
    		$errors[] = 'Subscription is expired';
    		return 0;
    	}

    	// check active date 
    	if (!empty($subs['subscription_active_at']) && $subs['subscription_active_at'] > date('Y-m-d H:i:s') ) {
    		$errors[] = 'Subscription is not active yet';
    		return 0;
    	}

    	// check minimal transaction 
    	if ( !empty($subs['subscription_user']['subscription']['subscription_minimal_transaction']) && $subs['subscription_user']['subscription']['subscription_minimal_transaction'] > $subtotal) {
    		$errors[] = 'Total transaction is not meet minimum transasction to use Subscription';
    		return 0;	
    	}

    	// check daily usage limit
    	if ( !empty($subs['subscription_user']['subscription']['daily_usage_limit']) ) {
			$subs_voucher_today = SubscriptionUserVoucher::where('id_subscription_user', '=', $id_subscription_user)
							->whereDate('used_at', date('Y-m-d'))
							->count();
			if ( $subs_voucher_today >= $subs['subscription_user']['subscription']['daily_usage_limit'] ) {
				$errors[] = 'Penggunaan subscription telah melampaui batas harian';
    			return 0;
			}
    	}

    	// check outlet
		$pct = new PromoCampaignTools;
		$check_outlet = $pct->checkOutletRule($id_outlet, $subs['subscription_user']['subscription']['is_all_outlet'], $subs['subscription_user']['subscription']['outlets_active'], $subs['subscription_user']['subscription']['id_brand']);

		if ( !$check_outlet ) {
    		$errors[] = 'Cannot use subscription at this outlet';
    		return 0;
    	}

    	// check product
    	if ( !empty($subs['subscription_user']['subscription']['subscription_products']) ) {
    		$promo_product = $subs['subscription_user']['subscription']['subscription_products'];
    		$check = false;
    		foreach ($promo_product as $key => $value) 
    		{
    			foreach ($item as $key2 => $value2) 
    			{
    				if ($value['id_product'] == $value2['id_product']) 
    				{
    					$check = true;
    					break;
    				}
    			}
    			if ($check) {
    				break;
    			}
    		}

    		if (!$check) {
    			$pct = new PromoCampaignTools;
    			$total_product = count($promo_product);
    			if ($total_product == 1) {
    				$product = $promo_product[0]['product']['product_name'] ?? 'product bertanda khusus';
    			}else{
    				$product = 'product bertanda khusus';
    			}


    			$message = $pct->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
				$message = MyHelper::simpleReplace($message,['product'=>$product]);
    			$errors[] = $message;
    			
				$getProduct  = app($this->promo_campaign)->getProduct('subscription',$subs['subscription_user']['subscription'], $id_outlet);
    			$product = $getProduct['product']??'';
    			$applied_product = $getProduct['applied_product'][0]??'';
    			$errorProduct = 1;
    			return 0;
    		}
    	}

    	// check shipment
    	if (isset($subs['subscription_user']['subscription']['is_all_shipment']) && isset($request['type']) ) {
    		$promo_shipment = $subs_obj->subscription_user->subscription->subscription_shipment_method->pluck('shipment_method');
    		$check_shipment = $pct->checkShipmentRule($subs['subscription_user']['subscription']['is_all_shipment'], $request['type'], $promo_shipment);

    		if(!$check_shipment){
				$errors[]='Promo cannot be used for this shipment method';
				return false;
			}
    	}

    	// check payment
    	if (isset($subs['subscription_user']['subscription']['is_all_payment']) 
    		&& isset($request['payment_type']) 
    		&& (isset($request['payment_id']) || isset($request['payment_detail'])) 
    	) {
    		$promo_payment = $subs_obj->subscription_user->subscription->subscription_payment_method->pluck('payment_method');
    		$payment_method = $pct->getPaymentMethod($request['payment_type'], $request['payment_id'], $request['payment_detail']);
    		$check_payment = $pct->checkPaymentRule($subs['subscription_user']['subscription']['is_all_payment'], $payment_method, $promo_payment);

    		if(!$check_payment){
				$errors[]='Promo cannot be used for this payment method';
				return false;
			}
    	}

		switch ($subs['subscription_user']['subscription']['subscription_discount_type']) {
			case 'discount_delivery':

				if( !empty($subs['subscription_user']['subscription']['subscription_voucher_nominal']) ){
					$discount_type = 'Nominal';
					$discount_value = $subs['subscription_user']['subscription']['subscription_voucher_nominal'];
					$max_percent_discount = 0;
				}elseif( !empty($subs['subscription_user']['subscription']['subscription_voucher_percent']) ){
					$discount_type = 'Percent';
					$discount_value = $subs['subscription_user']['subscription']['subscription_voucher_percent'];
					$max_percent_discount = $subs['subscription_user']['subscription']['subscription_voucher_percent_max'];
				}else{
		    		$errors[] = 'Subscription not valid.';
		    		return 0;
		    	}

				if (!empty($delivery_fee)) {
					$result = $pct->discountDelivery(
						$delivery_fee, 
						$discount_type,
						$discount_value,
						$max_percent_discount
					);
				}
				else{
					$result = 0;
				}
				break;
			
			default:
				/*
					subscription type
					- payment_method
					- discount
				*/

				// sum subs discount
		    	if ( !empty($subs['subscription_user']['subscription']['subscription_voucher_nominal']) ) 
		    	{
		    		$result = $subs['subscription_user']['subscription']['subscription_voucher_nominal'];

		    		if ( $result > $grandtotal ) 
					{
						$result = $grandtotal;
					}
		    	}
		    	elseif( !empty($subs['subscription_user']['subscription']['subscription_voucher_percent']) )
		    	{
		    		$result = $grandtotal * ($subs['subscription_user']['subscription']['subscription_voucher_percent']/100);

		    		if ( !empty($subs['subscription_user']['subscription']['subscription_voucher_percent_max']) ) 
		    		{
		    			if ( $result > $subs['subscription_user']['subscription']['subscription_voucher_percent_max'] ) 
		    			{
		    				$result = $subs['subscription_user']['subscription']['subscription_voucher_percent_max'];
		    			}
		    		}
		    	}
		    	else
		    	{
		    		$errors[] = 'Subscription not valid.';
		    		return 0;
		    	}

				break;
		}


    	return [
    		'type' => $type,
    		'value' => $result
    	];
    }

    function checkDiscount($request, $post)
    {
    	$data_subs = SubscriptionUser::where('id_subscription_user', $request->id_subscription_user)->with('subscription')->first();
		if (!$data_subs) {
			return [
                'status'=>'fail',
                'messages'=>['Promo is not valid']
            ];	
		}
		$subs_type = $data_subs['subscription']['subscription_discount_type'];

		if ($subs_type != 'payment_method') {
        	$check_subs = $this->calculate($request, $request->id_subscription_user, $post['subtotal'], $post['subtotal'], $post['item'], $post['id_outlet'], $subs_error, $errorProduct, $subs_product, $subs_applied_product, $post['delivery_fee']??0);

        	if (!empty($subs_error)) {
                return [
                    'status'    => 'fail',
                    'messages'  => ['Promo not valid']
                ];
	        }

	        return MyHelper::checkGet($check_subs);
		}else{
	        return MyHelper::checkGet(['type' => $subs_type]);
		}
    }
}