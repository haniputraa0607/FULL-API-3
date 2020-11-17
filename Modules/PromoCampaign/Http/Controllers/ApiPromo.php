<?php

namespace Modules\PromoCampaign\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignOutlet;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignProductDiscount;
use Modules\PromoCampaign\Entities\PromoCampaignProductDiscountRule;
use Modules\PromoCampaign\Entities\PromoCampaignTierDiscountProduct;
use Modules\PromoCampaign\Entities\PromoCampaignTierDiscountRule;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductRequirement;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyRule;
use Modules\PromoCampaign\Entities\PromoCampaignHaveTag;
use Modules\PromoCampaign\Entities\PromoCampaignTag;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\UserPromo;;

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;

use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;

use Modules\ProductVariant\Entities\ProductGroup;

use App\Http\Models\User;
use App\Http\Models\Configs;
use App\Http\Models\Campaign;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Setting;
use App\Http\Models\Voucher;
use App\Http\Models\Treatment;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;

use Modules\PromoCampaign\Http\Requests\Step1PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\Step2PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\DeletePromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\ValidateCode;
use Modules\PromoCampaign\Http\Requests\UpdateCashBackRule;
use Modules\PromoCampaign\Http\Requests\CheckUsed;

use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use DB;
use Hash;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\LogCheckPromoCode;

use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\BrandOutlet;

class ApiPromo extends Controller
{

	function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_transaction   = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->voucher   = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->fraud   = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use   = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
    }

    public function checkUsedPromo(CheckUsed $request)
    {
    	$user = auth()->user();
    	$datenow = date("Y-m-d H:i:s");
    	$remove = 0;
		DB::beginTransaction();
    	$user_promo = UserPromo::where('id_user','=',$user->id)->first();
    	if (!$user_promo) {
    		return response()->json(['status' => 'fail']);
    	}
    	if ($user_promo->promo_type == 'deals')
    	{
    		$promo = app($this->promo_campaign)->checkVoucher(null, null, 1, 1);

    		if ($promo) {
    			if ($promo->used_at) {
    				$remove = 1;
    			}elseif($promo->voucher_expired_at < $datenow){
    				$remove = 1;
    			}
    		}

    	}
    	elseif ( $user_promo->promo_type == 'promo_campaign' )
    	{
    		$promo = app($this->promo_campaign)->checkPromoCode(null, 1, 1, $user_promo->id_reference, 1);
    		if ($promo) 
			{
				if ($promo->date_end < $datenow) {
					$remove = 1;
				}else{
					$pct = new PromoCampaignTools;
					$validate_user=$pct->validateUser($promo->id_promo_campaign, $user->id, $user->phone, null, $request->device_id, $error,$promo->id_promo_campaign_promo_code);
					if (!$validate_user) {
						$remove = 1;
					}
				}
			}
    	}
    	elseif ( $user_promo->promo_type == 'subscription' )
    	{
    		$promo = app($this->subscription_use)->checkSubscription(null, null, 1, 1, null, $user_promo->id_reference, 1, 1);

    		if ($promo) {
    			if ($promo->subscription_expired_at < $datenow) {
    				$remove = 1;
    			}elseif ( $promo->subscription_user->subscription->daily_usage_limit ) {
					$subs_voucher_today = SubscriptionUserVoucher::where('id_subscription_user', '=', $promo->id_subscription_user)
											->whereDate('used_at', date('Y-m-d'))
											->count();
					if ( $subs_voucher_today >= $promo->subscription_user->subscription->daily_usage_limit ) {
						$remove = 1;
					}
		    	}
    		}
    	}
    	else
    	{
    		return response()->json(['status' => 'fail']);
    	}

    	if (!$promo) {
    		return response()->json(['status' => 'fail']);
    	}

    	$promo = $promo->toArray();

    	$getProduct = app($this->promo_campaign)->getProduct($user_promo->promo_type,$promo['deal_voucher']['deals']??$promo['promo_campaign']??$promo['subscription_user']['subscription']);
    	$desc = app($this->promo_campaign)->getPromoDescription($user_promo->promo_type, $promo['deal_voucher']['deals']??$promo['promo_campaign']??$promo['subscription_user']['subscription'], $getProduct['product']??'');

    	$result = [
    		'title'				=> $promo['deal_voucher']['deals']['deals_title']??$promo['promo_campaign']['promo_title']??$promo['subscription_user']['subscription']['subscription_title'],
    		'description'		=> $desc,
    		'id_deals_user'		=> $promo['id_deals_user']??'',
    		'promo_code'		=> $promo['promo_code']??'',
    		'id_subscription_user'		=> $promo['id_subscription_user']??'',
    		'remove'			=> $remove
    	];
    	return response()->json(MyHelper::checkGet($result));

    }

    public function usePromo($source, $id_promo, $status='use', $query=null)
    {
    	$user = auth()->user();
		DB::beginTransaction();
		// change is used flag to 0
		$update = DealsUser::where('id_user','=',$user->id)->where('is_used','=',1)->update(['is_used' => 0]);
		$update = SubscriptionUser::where('id_user','=',$user->id)->where('is_used','=',1)->update(['is_used' => 0]);

		if ($status == 'use')
		{

			if ($source == 'deals')
			{
				// change specific deals user is used to 1
				$update = DealsUser::where('id_deals_user','=',$id_promo)->update(['is_used' => 1]);
			}
			elseif($source == 'subscription')
			{
				$update = SubscriptionUser::where('id_subscription_user','=',$query['id_subscription_user'])->update(['is_used' => 1]);
			}

			$update = UserPromo::updateOrCreate(['id_user' => $user->id], ['promo_type' => $source, 'id_reference' => $id_promo]);
		}
		else
		{
			$update = UserPromo::where('id_user', '=', $user->id)->delete();
		}

		if ($update) {
			DB::commit();
		}else{
			DB::rollback();
		}

		if (is_numeric($update)) {
			$update = 1;
		}
		$update = MyHelper::checkUpdate($update);

		$update['webview_url'] = "";
		$update['webview_url_v2'] = "";
		if ($source == 'deals')
		{
			$update['webview_url'] = config('url.api_url') ."api/webview/voucher/". $id_promo;
			$update['webview_url_v2'] = config('url.api_url') ."api/webview/voucher/v2/". $id_promo;
		}
		elseif($source == 'subscription')
		{
			if ($id_promo) {
				$update['webview_url'] = config('url.api_url') ."api/webview/mysubscription/". $id_promo;
			}
		}

		return $update;

    }

    public function cancelPromo(Request $request)
    {
    	$post = $request->json()->all();

    	if (!empty($post['id_deals_user']))
    	{
    		$source = 'deals';
    	}
    	elseif (!empty($post['id_subscription_user']))
    	{
    		$source = 'subscription';
    	}
    	else
    	{
    		$source = 'promo_campaign';
    	}
    	$cancel = $this->usePromo($source, $post['id_deals_user']??$post['id_subscription_user']??'', 'cancel');

    	if ($cancel) {
    		return response()->json($cancel);
    	}else{
    		return response()->json([
    			'status' => 'fail',
    			'messages' => 'Failed to update promo'
    		]);
    	}
    }

    public function promoGetCashbackRule()
    {
    	$getData = Configs::whereIn('config_name',['promo code get point','voucher offline get point','voucher online get point','subscription get point'])->get()->toArray();

    	foreach ($getData as $key => $value) {
    		$config[$value['config_name']] = $value['is_active'];
    	}

    	return $config;
    }

    public function getDataCashback(Request $request)
    {
    	$data = $this->promoGetCashbackRule();

    	return response()->json(myHelper::checkGet($data));
    }

    public function updateDataCashback(UpdateCashBackRule $request)
    {
    	$post = $request->json()->all();
    	db::beginTransaction();
    	$update = Configs::where('config_name','promo code get point')->update(['is_active' => $post['promo_code_cashback']??0]);
    	$update = Configs::where('config_name','voucher online get point')->update(['is_active' => $post['voucher_online_cashback']??0]);
    	$update = Configs::where('config_name','voucher offline get point')->update(['is_active' => $post['voucher_offline_cashback']??0]);
    	$update = Configs::where('config_name','subscription get point')->update(['is_active' => $post['subscription_cashback']??0]);

    	if(is_numeric($update))
    	{
    		db::commit();
    	}else{
    		db::rollback();
    	}

    	return response()->json(myHelper::checkUpdate($update));
    }

    public function availablePromo()
    {
    	$available_deals = DealsUser::where('id_user', auth()->user()->id)
			            ->whereIn('paid_status', ['Free', 'Completed'])
			            ->whereNull('used_at')
			            ->where('voucher_expired_at', '>', date('Y-m-d H:i:s'))
			            ->count();

        $available_subs = SubscriptionUser::where('id_user', auth()->user()->id)
		                ->where('subscription_expired_at', '>=',date('Y-m-d H:i:s'))
		                ->whereIn('paid_status', ['Completed','Free'])
		                ->whereHas('subscription_user_vouchers', function($q){
		                	$q->whereNull('used_at');
		                })
			            ->count();

		return ($available_deals+$available_subs);
    }

    public function checkMinBasketSize($promo_source, $query, $subtotal_per_brand)
    {
    	$check = false;
    	$min_basket_size = 0;
    	switch ($promo_source) {
    		case 'promo_code':
    			$min_basket_size = $query->min_basket_size;
    			$promo_brand = $query->promo_campaign->promo_campaign_brands->pluck('id_brand')->toArray();
    			break;
    		
    		case 'voucher_online':
    			$min_basket_size = $query->dealVoucher->deals->min_basket_size;
    			$promo_brand = $query->dealVoucher->deals->deals_brands->pluck('id_brand')->toArray();
    			break;
    		
    		default:
    			# code...
    			break;
    	}

    	if (empty($min_basket_size)) {
    		$check = true;
    	}else{
    		$promo_brand_flipped = array_flip($promo_brand);
    		foreach ($subtotal_per_brand as $key => $value) {
    			if (!isset($promo_brand_flipped[$key])) {
    				continue;
    			}
    			if ($value >= $min_basket_size) {
    				$check = true;
    				break;
    			}
    		}
    	}
    	
    	return $check;
    }

    public function checkPromo($request, $user, $promo_source, $data_promo, $id_outlet, $item, $delivery_fee, $subtotal)
    {
    	$pct = new PromoCampaignTools;
    	if ($promo_source == 'promo_code') {
    		$validate_user = $pct->validateUser(
    			$data_promo->id_promo_campaign, 
    			$user->id, 
    			$user->phone, 
    			$request->device_type, 
    			$request->device_id, 
    			$errore,
    			$data_promo->id_promo_campaign_promo_code
    		);

    		$source = 'promo_campaign';
    		$id_promo = $data_promo->id_promo_campaign;

            if ( !empty($errore) ) {
                return [
                    'status'	=> 'fail',
                    'messages'	=> ['Promo code not valid']
                ];
            }
    	}
    	elseif ($promo_source == "voucher_online") {
    		$source = 'deals';
    		$id_promo = $data_promo->dealVoucher->id_deals;
    	}

		$discount_promo = $pct->validatePromo(
			$request,
			$id_promo, 
			$id_outlet, 
			$item, 
			$errors, 
			$source, 
			$errorProduct, 
			$delivery_fee
		);

		if ( !empty($errors) ) {
            return [
                'status' 	=> 'fail',
                'messages'	=> ['Promo is not valid']
            ];
        }

        return [
        	'status' => 'success',
        	'data'	 => $discount_promo
        ];
    }

    public function getTransactionCheckPromoRule($result, $promo_source, $query)
    {
    	$check = false;
    	$available_shipment = ['Pickup Order', 'GO-SEND'];
    	$available_payment 	= $this->getAvailablePayment()['result'];
    	$result['pickup_type'] = 1;
    	$result['delivery_type'] = 1;
    	$result['available_payment'] = [];

    	switch ($promo_source) {
    		case 'promo_code':
    			$promo = $query;
    			$promo_shipment = $query->promo_campaign->promo_campaign_shipment_method->pluck('shipment_method');
    			$promo_payment 	= $query->promo_campaign->promo_campaign_payment_method->pluck('payment_method');
    			break;
    		
    		case 'voucher_online':
    			$promo = $query->dealVoucher->deals;
    			$promo_shipment = $query->dealVoucher->deals->deals_shipment_method->pluck('shipment_method');
    			$promo_payment 	= $query->dealVoucher->deals->deals_payment_method->pluck('payment_method');
    			break;
    		
    		default:
    			# code...
    			break;
    	}

    	$pct = New PromoCampaignTools;
    	if ($promo_shipment) {
	    	if (!$pct->checkShipmentRule($promo->is_all_shipment, 'Pickup Order', $promo_shipment)) {
	    		$result['pickup_type'] = 0;
	    	}
	    	if (!$pct->checkShipmentRule($promo->is_all_shipment, 'GO-SEND', $promo_shipment)) {
	    		$result['delivery_type'] = 0;
	    	}
    	}

    	if ($promo_payment) {
    		foreach ($available_payment as $key => $value) {
    			if ($pct->checkPaymentRule($promo->is_all_payment, $value['payment_method'], $promo_payment)) {
		    		$result['available_payment'][] = $value['code'];
		    	}	
    		}
    	}
    	
    	return $result;
    }

    public function getAvailablePayment()
    {
    	$custom_data 	= [];
    	$custom_request = new \Illuminate\Http\Request;
		$custom_request = $custom_request
						->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($custom_data))
						->merge($custom_data);

		$payment_list 	= app($this->online_transaction)->availablePayment($custom_request);

		return $payment_list;
    }

    public function checkBrandProduct($outlets = [], $products = [])
    {
    	$result	= [
    		'status' => true,
    		'messages' => []
    	];

    	if (isset($outlets[0]['id_outlet'])) {
    		if (!is_array($outlets)) {
	    		$outlets = $outlets->toArray();
	    	}
    		$outlets = array_column($outlets, 'id_outlet');
    	}

    	$outlet = BrandOutlet::select('id_brand', 'id_outlet')->whereIn('id_outlet', $outlets)->get()->toArray();
    	$brand_outlet = array_column($outlet, 'id_brand');

    	if (isset($products[0]['id_brand'])) {
    		
    	}
    	$brand_product 	= [];
    	foreach ($products as $value) {

    		if (isset($value['id_brand'])) {
    			$get_product_brand = $value['id_brand'];
    		}else{
    			$get_product_brand = app($this->promo_campaign)->splitBrandProduct($value, 'brand');
    		}

    		if (empty($get_product_brand)) {
    			continue;
    		}

    		$brand_product[] = $get_product_brand;
    	}

    	// if product doesn't have brand then return true
    	if (empty($brand_product)) {
    		return $result;
    	}

    	$brand_product 	= array_flip($brand_product);

    	$outlet_invalid = [];
    	$outlet_valid 	= [];
    	foreach ($brand_outlet as $key => $value) {
    		if (!isset($brand_product[$value])) {
    			$outlet_invalid[] = $outlet[$key]['id_outlet'];
    		}else{
    			$outlet_valid[] = $outlet[$key]['id_outlet'];
    		}
    	}
		
		$invalid 	= array_flip(array_flip(array_diff($outlet_invalid, $outlet_valid)));

		$messages = [];
		if (!empty($invalid)) {
			$outlet_name = Outlet::whereIn('id_outlet', $invalid)->pluck('outlet_name')->toArray();
			$result['status'] 	= false;
			$result['messages'] = array_merge(["Outlet tidak mempunyai produk dengan brand yang sesuai."],$outlet_name);
		}

		return $result;
    }
}
