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

    public function calculate($id_subscription_user, $grandtotal, $subtotal, $item, $id_outlet, &$errors, &$errorProduct=0, &$product="", &$applied_product="")
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

    	$subs = $subs->toArray();

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
    		// $errors[] = 'Total transaction is not meet minimum transasction to use Subscription';
    		$errors[] = 'Total transaksi belum mencapai syarat minimum untuk menggunakan Subscription ini.';
    		$errorProduct = 'all';
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
    	// if ( empty($subs['subscription_user']['subscription']['is_all_outlet']) ) {
    	// }
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
    			$message = $pct->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
				$message = MyHelper::simpleReplace($message,['product'=>'product bertanda khusus']);
    			$errors[] = $message;
    			
				$getProduct  = app($this->promo_campaign)->getProduct('subscription',$subs['subscription_user']['subscription'], $id_outlet);
    			$product = $getProduct['product']??'';
    			$applied_product = $getProduct['applied_product'][0]??'';
    			$errorProduct = 1;
    			return 0;
    		}
    	}

		// sum subs discount
    	if ( !empty($subs['subscription_user']['subscription']['subscription_voucher_nominal']) ) 
    	{
    		$subs_total = $subs['subscription_user']['subscription']['subscription_voucher_nominal'];

    		if ( $subs_total > $grandtotal ) 
			{
				$subs_total = $grandtotal;
			}
    	}
    	elseif( !empty($subs['subscription_user']['subscription']['subscription_voucher_percent']) )
    	{
    		$subs_total = $grandtotal * ($subs['subscription_user']['subscription']['subscription_voucher_percent']/100);

    		if ( !empty($subs['subscription_user']['subscription']['subscription_voucher_percent_max']) ) 
    		{
    			if ( $subs_total > $subs['subscription_user']['subscription']['subscription_voucher_percent_max'] ) 
    			{
    				$subs_total = $subs['subscription_user']['subscription']['subscription_voucher_percent_max'];
    			}
    		}
    	}
    	else
    	{
    		$errors[] = 'Subscription not valid.';
    		return 0;
    	}

    	return $subs_total;
    }
}
