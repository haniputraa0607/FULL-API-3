<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:34:14 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;
use App\Lib\MyHelper;

/**
 * Class Subscription
 * 
 * @property int $id_subscription
 * @property string $subscription_title
 * @property string $subscription_sub_title
 * @property string $subscription_image
 * @property \Carbon\Carbon $subscription_start
 * @property \Carbon\Carbon $subscription_end
 * @property \Carbon\Carbon $subscription_publish_start
 * @property \Carbon\Carbon $subscription_publish_end
 * @property int $subscription_price_point
 * @property float $subscription_price_cash
 * @property string $subscription_description
 * @property string $subscription_term
 * @property string $subscription_how_to_use
 * @property int $subscription_bought
 * @property int $subscription_total
 * @property int $subscription_day_valid
 * @property int $subscription_voucher_total
 * @property int $subscription_voucher_nominal
 * @property int $subscription_minimal_transaction
 * @property bool $is_all_outlet
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection $featured_subscriptions
 * @property \Illuminate\Database\Eloquent\Collection $outlets
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package Modules\Subscription\Entities
 */
class Subscription extends Eloquent
{
	protected $primaryKey = 'id_subscription';

	protected $casts = [
		'subscription_price_point' => 'int',
		'subscription_price_cash' => 'float',
		'subscription_bought' => 'int',
		'subscription_total' => 'int',
		'subscription_day_valid' => 'int',
		'subscription_voucher_total' => 'int',
		'subscription_voucher_nominal' => 'int',
		'subscription_minimal_transaction' => 'int',
		'is_all_outlet' => 'bool'
	];

	protected $dates = [
		'subscription_start',
		'subscription_end',
		'subscription_publish_start',
		'subscription_publish_end'
	];

	protected $fillable = [
	    'id_brand',
		'subscription_title',
		'subscription_sub_title',
		'subscription_image',
		'subscription_start',
		'subscription_end',
		'subscription_publish_start',
		'subscription_publish_end',
		'subscription_price_point',
		'subscription_price_cash',
		'subscription_description',
		'subscription_bought',
		'subscription_total',
		'user_limit',
		'subscription_voucher_duration',
		'subscription_voucher_start',
		'subscription_voucher_expired',
		'subscription_voucher_total',
		'subscription_voucher_nominal',
		'subscription_voucher_percent',
		'subscription_voucher_percent_max',
		'subscription_minimal_transaction',
		'daily_usage_limit',
		'new_purchase_after',
		'is_all_outlet',
		'subscription_step_complete'
	];

	protected $appends  = [
		'url_subscription_image', 
		'subscription_status', 
		'subscription_price_type', 
		'subscription_price_pretty',
		'url_webview'
	];

	public function getUrlWebviewAttribute() {
		return env('APP_API_URL') ."api/webview/subscription/". $this->id_subscription;
	}

	public function getSubscriptionPricePrettyAttribute() {
	    $pretty = "Gratis";
		if ($this->subscription_price_point) {
            $pretty = MyHelper::requestNumber($this->subscription_price_point,'_POINT');
        }
        elseif ($this->subscription_price_cash) {
            $pretty = MyHelper::requestNumber($this->subscription_price_cash,'_CURRENCY');
        }
        return $pretty;
	}

	public function getSubscriptionPriceTypeAttribute() {
	    $type = "free";
	    // if ($this->subscription_price_point && $this->subscription_price_cash) {
     //        $type = "all";
	    // }
		if ($this->subscription_price_point) {
            $type = "point";
        }
        else if ($this->subscription_price_cash) {
            $type = "nominal";
        }
        return $type;
	}

	public function getSubscriptionStatusAttribute() {
	    $status = "";
		if (date('Y-m-d H:i:s', strtotime($this->subscription_start)) <= date('Y-m-d H:i:s') && date('Y-m-d H:i:s', strtotime($this->subscription_end)) > date('Y-m-d H:i:s')) {
            $status = "available";
        }
        else if (date('Y-m-d H:i:s', strtotime($this->subscription_start)) > date('Y-m-d H:i:s')) {
            $status = "soon";
        }
        else if (date('Y-m-d H:i:s', strtotime($this->subscription_end)) < date('Y-m-d H:i:s')) {
            $status = "expired";
        }
        return $status;
	}


	// ATTRIBUTE IMAGE URL
	public function getUrlSubscriptionImageAttribute() {
		if (empty($this->subscription_image)) {
            return env('S3_URL_API').'img/default.jpg';
        }
        else {
            return env('S3_URL_API').$this->subscription_image;
        }
	}

	public function featured_subscriptions()
	{
		return $this->hasMany(\Modules\Subscription\Entities\FeaturedSubscription::class, 'id_subscription');
	}

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'subscription_outlets', 'id_subscription', 'id_outlet')
					->withPivot('id_subscription_outlets')
					->withTimestamps();
	}

	public function users()
	{
		return $this->belongsToMany(\App\Http\Models\User::class, 'subscription_users', 'id_subscription', 'id_user')
					->withPivot('id_subscription_user', 'bought_at', 'subscription_expired_at')
					->withTimestamps();
	}

	public function subscription_payment_midtrans()
	{
		return $this->hasMany(\Modules\Subscription\Entities\SubscriptionPaymentMidtran::class, 'id_subscription');
	}

	public function subscription_content()
	{
		return $this->hasMany(\Modules\Subscription\Entities\SubscriptionContent::class, 'id_subscription');
	}

	public function subscription_users()
	{
		return $this->hasMany(\Modules\Subscription\Entities\SubscriptionUser::class, 'id_subscription');
	}

	public function subscription_products()
	{
		return $this->hasMany(\Modules\Subscription\Entities\SubscriptionProduct::class, 'id_subscription');
	}

	public function outlets_active()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'subscription_outlets', 'id_subscription', 'id_outlet')->where('outlet_status', 'Active');
	}

	public function products()
	{
		return $this->belongsToMany(\App\Http\Models\Product::class, 'subscription_products', 'id_subscription', 'id_product')
					->withPivot('id_subscription_product')
					->withTimestamps();
	}

    public function brand(){
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class,'id_brand');
	}
}
