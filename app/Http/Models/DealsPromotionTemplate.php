<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DealsPromotionTemplate extends Model
{
	protected $primaryKey = 'id_deals_promotion_template';

	protected $casts = [
		'created_by' => 'int',
		'last_updated_by' => 'int',
		'deals_nominal' => 'int',
		'deals_voucher_value' => 'int',
		'deals_voucher_given' => 'int',
		'deals_total_voucher' => 'int',
		'deals_voucher_duration' => 'int',
		'user_limit' => 'int',
		'is_online' => 'bool',
		'is_offline' => 'bool',
		'step_complete' => 'bool'
	];

	protected $dates = [
		'deals_start',
		'deals_end',
		'deals_voucher_start',
		'deals_voucher_expired'
	];

	protected $fillable = [
		'created_by',
		'last_updated_by',
		'deals_title',
		'deals_second_title',
		'deals_description',
		'deals_short_description',
		'deals_image',
		'deals_warning_image',
		'deals_voucher_type',
		'deals_promo_id_type',
		'deals_promo_id',
		'deals_nominal',
		'deals_voucher_value',
		'deals_voucher_given',
		'deals_start',
		'deals_end',
		'deals_total_voucher',
		'deals_list_voucher',
		'deals_voucher_duration',
		'deals_voucher_start',
		'deals_voucher_expired',
		'user_limit',
		'promo_type',
		'is_online',
		'is_offline',
		'step_complete',
		'custom_outlet_text',
		'charged_central',
        'charged_outlet',
		'product_type',
		'deals_list_outlet',
		'id_brand',
		'min_basket_size',
		'is_all_shipment',
        'is_all_payment'
	];

	public function deals_promotion_buyxgety_product_requirement()
	{
		return $this->hasOne(\Modules\Promotion\Entities\DealsPromotionBuyxgetyProductRequirement::class, 'id_deals');
	}

	public function deals_promotion_buyxgety_rules()
	{
		return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionBuyxgetyRule::class, 'id_deals');
	}

	public function deals_promotion_content()
	{
		return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionContent::class, 'id_deals');
	}

	public function deals_promotion_product_discount_rules()
	{
		return $this->hasOne(\Modules\Promotion\Entities\DealsPromotionProductDiscountRule::class, 'id_deals');
	}

	public function deals_promotion_product_discount()
	{
		return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionProductDiscount::class, 'id_deals');
	}

	public function deals_promotion_tier_discount_product()
	{
		return $this->hasOne(\Modules\Promotion\Entities\DealsPromotionTierDiscountProduct::class, 'id_deals');
	}

	public function deals_promotion_tier_discount_rules()
	{
		return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionTierDiscountRule::class, 'id_deals');
	}

	public function promotion_contents()
	{
		return $this->hasMany(\App\Http\Models\PromotionContent::class, 'id_deals_promotion_template');
	}

	protected $appends  = ['url_deals_image', 'url_deals_warning_image'];

	// ATTRIBUTE IMAGE URL
	public function getUrlDealsImageAttribute() {
		if (empty($this->deals_image)) {
            return config('url.storage_url_api').'img/default.jpg';
        }
        else {
            return config('url.storage_url_api').$this->deals_image;
        }
	}

	// ATTRIBUTE WARNING IMAGE URL
	public function getUrlDealsWarningImageAttribute() {
		if (empty($this->deals_warning_image)) {
            return null;
        }
        else {
            return env('S3_URL_API').$this->deals_warning_image;
        }
	}

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'deals_outlets', 'id_deals', 'id_outlet');
	}

	public function deals_vouchers()
	{
		return $this->hasMany(\App\Http\Models\DealsVoucher::class, 'id_deals');
	}

	public function deals_subscriptions()
	{
		return $this->hasMany(DealsSubscription::class, 'id_deals');
	}
	public function created_by_user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'created_by');
    }

    public function brand(){
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class,'id_brand');
	}

	public function deals_promotion_discount_bill_rules()
	{
		return $this->hasOne(\Modules\Promotion\Entities\DealsPromotionDiscountBillRule::class, 'id_deals');
	}

	public function deals_promotion_discount_delivery_rules()
	{
		return $this->hasOne(\Modules\Promotion\Entities\DealsPromotionDiscountDeliveryRule::class, 'id_deals');
	}

	public function deals_promotion_shipment_method()
    {
        return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionShipmentMethod::class, 'id_deals');
    }

    public function deals_promotion_payment_method()
    {
        return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionPaymentMethod::class, 'id_deals');
    }
}
