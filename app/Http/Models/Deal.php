<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use \App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Deal
 *
 * @property int $id_deals
 * @property string $deals_type
 * @property string $deals_voucher_type
 * @property string $deals_promo_id
 * @property string $deals_title
 * @property string $deals_second_title
 * @property string $deals_description
 * @property string $deals_short_description
 * @property string $deals_image
 * @property string $deals_video
 * @property int $id_product
 * @property \Carbon\Carbon $deals_start
 * @property \Carbon\Carbon $deals_end
 * @property \Carbon\Carbon $deals_publish_start
 * @property \Carbon\Carbon $deals_publish_end
 * @property int $deals_voucher_duration
 * @property \Carbon\Carbon $deals_voucher_expired
 * @property int $deals_voucher_price_point
 * @property int $deals_voucher_price_cash
 * @property int $deals_total_voucher
 * @property int $deals_total_claimed
 * @property int $deals_total_redeemed
 * @property int $deals_total_used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\Product $product
 * @property \Illuminate\Database\Eloquent\Collection $outlets
 * @property \Illuminate\Database\Eloquent\Collection $deals_payment_manuals
 * @property \Illuminate\Database\Eloquent\Collection $deals_payment_midtrans
 * @property \Illuminate\Database\Eloquent\Collection $deals_vouchers
 *
 * @package App\Models
 */
class Deal extends Model
{
	protected $primaryKey = 'id_deals';

	protected $casts = [
		'id_product' => 'int',
		'deals_voucher_duration' => 'int',
		'deals_voucher_price_point' => 'int',
		'deals_voucher_price_cash' => 'int',
		'deals_total_voucher' => 'int',
		'total_voucher_subscription' => 'int',
		'deals_total_claimed' => 'int',
		'deals_total_redeemed' => 'int',
		'deals_total_used' => 'int'
	];

	protected $dates = [
		'deals_start',
		'deals_end',
		'deals_publish_start',
		'deals_publish_end',
		'deals_voucher_expired'
	];

	protected $fillable = [
		'deals_type',
		'created_by',
		'last_updated_by',
		'deals_voucher_type',
		'deals_promo_id_type',
		'deals_promo_id',
		'deals_title',
		'deals_second_title',
		// 'deals_description',
		// 'deals_tos',
		// 'deals_short_description',
		'deals_image',
		// 'deals_video',
		'id_brand',
		'id_product',
		'deals_start',
		'deals_end',
		'deals_publish_start',
		'deals_publish_end',
		'deals_voucher_start',
		'deals_voucher_duration',
		'deals_voucher_expired',
		'deals_voucher_price_point',
		'deals_voucher_price_cash',
		'deals_total_voucher',
		'total_voucher_subscription',
		'deals_total_claimed',
		'deals_total_redeemed',
		'deals_total_used',
		'claim_allowed',
		'user_limit',
		'is_online',
		'is_offline',
		'promo_type',
		'product_type',
        'charged_central',
        'charged_outlet',
        'is_all_outlet',
        'custom_outlet_text',
        'min_basket_size',
        'is_all_shipment',
        'is_all_payment',
        'product_rule'
	];

	protected $appends  = ['url_deals_image', 'deals_status', 'deals_voucher_price_type', 'deals_voucher_price_pretty', 'url_webview'];

	public function getUrlWebviewAttribute() {
		return config('url.api_url') ."api/webview/deals/". $this->id_deals ."/". $this->deals_type;
	}

	public function getDealsVoucherPriceTypeAttribute() {
	    $type = "free";
		if ($this->deals_voucher_price_point) {
            $type = "point";
        }
        else if ($this->deals_voucher_price_cash) {
            $type = "nominal";
        }
        return $type;
	}

	public function getDealsVoucherPricePrettyAttribute() {
	    $pretty = "Free";
		if ($this->dealsVoucherPriceType == 'point') {
            $pretty = MyHelper::requestNumber($this->deals_voucher_price_point,'_POINT');
        }
        elseif ($this->dealsVoucherPriceType == 'nominal') {
            // $pretty = MyHelper::requestNumber($this->deals_voucher_price_cash,'_CURRENCY');
            $pretty = 'Rp '.MyHelper::requestNumber($this->deals_voucher_price_cash,'thousand_id');
        }
        return $pretty;
	}

	public function getDealsStatusAttribute() {
	    $status = "";
		if (date('Y-m-d H:i:s', strtotime($this->deals_start)) <= date('Y-m-d H:i:s') && date('Y-m-d H:i:s', strtotime($this->deals_end)) > date('Y-m-d H:i:s')) {
            $status = "available";
        }
        else if (date('Y-m-d H:i:s', strtotime($this->deals_start)) > date('Y-m-d H:i:s')) {
            $status = "soon";
        }
        else if (date('Y-m-d H:i:s', strtotime($this->deals_end)) < date('Y-m-d H:i:s')) {
            $status = "expired";
        }
        return $status;
	}


	// ATTRIBUTE IMAGE URL
	public function getUrlDealsImageAttribute() {
		if (empty($this->deals_image)) {
            return config('url.storage_url_api').'img/default.jpg';
        }
        else {
            return config('url.storage_url_api').$this->deals_image;
        }
	}

	public function brand(){
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class,'id_brand');
	}

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'deals_outlets', 'id_deals', 'id_outlet');
	}

	public function outlets_active()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'deals_outlets', 'id_deals', 'id_outlet')->where('outlet_status', 'Active');
	}

	public function deals_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\DealsPaymentManual::class, 'id_deals');
	}

	public function deals_payment_midtrans()
	{
		return $this->hasMany(\App\Http\Models\DealsPaymentMidtran::class, 'id_deals');
	}

	public function deals_vouchers()
	{
		return $this->hasMany(\App\Http\Models\DealsVoucher::class, 'id_deals');
	}

	public function deals_subscriptions()
	{
		return $this->hasMany(DealsSubscription::class, 'id_deals');
	}

	public function featured_deals()
	{
		return $this->hasOne(FeaturedDeal::class, 'id_deals','id_deals');
	}

	public function deals_buyxgety_rules()
	{
		return $this->hasMany(\Modules\Deals\Entities\DealsBuyxgetyRule::class, 'id_deals');
	}

	public function deals_product_discount_rules()
	{
		return $this->hasOne(\Modules\Deals\Entities\DealsProductDiscountRule::class, 'id_deals');
	}

	public function deals_tier_discount_rules()
	{
		return $this->hasMany(\Modules\Deals\Entities\DealsTierDiscountRule::class, 'id_deals');
	}

	public function deals_buyxgety_product_requirement()
    {
        return $this->hasOne(\Modules\Deals\Entities\DealsBuyxgetyProductRequirement::class, 'id_deals', 'id_deals');
    }

    public function deals_tier_discount_product()
    {
        return $this->belongsTo(\Modules\Deals\Entities\DealsTierDiscountProduct::class, 'id_deals', 'id_deals');
    }

    public function deals_product_discount()
    {
        return $this->hasMany(\Modules\Deals\Entities\DealsProductDiscount::class, 'id_deals', 'id_deals');
    }

    public function deals_content()
    {
        return $this->hasMany(\Modules\Deals\Entities\DealsContent::class, 'id_deals', 'id_deals');
    }

    public function created_by_user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'created_by');
    }

    public function deals_discount_bill_rules()
	{
		return $this->hasOne(\Modules\Deals\Entities\DealsDiscountBillRule::class, 'id_deals');
	}

	public function deals_discount_delivery_rules()
	{
		return $this->hasOne(\Modules\Deals\Entities\DealsDiscountDeliveryRule::class, 'id_deals');
	}

	public function deals_shipment_method()
    {
        return $this->hasMany(\Modules\Deals\Entities\DealsShipmentMethod::class, 'id_deals', 'id_deals');
    }

    public function deals_payment_method()
    {
        return $this->hasMany(\Modules\Deals\Entities\DealsPaymentMethod::class, 'id_deals', 'id_deals');
    }
}
