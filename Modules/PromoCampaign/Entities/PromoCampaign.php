<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:39:02 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaign
 * 
 * @property int $id_promo_campaign
 * @property int $created_by
 * @property int $last_updated_by
 * @property string $campaign_name
 * @property string $promo_title
 * @property string $code_type
 * @property string $prefix_code
 * @property int $number_last_code
 * @property int $total_code
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property string $is_all_outlet
 * @property string $promo_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $used_code
 * @property int $limitation_usage
 * 
 * @property \Illuminate\Database\Eloquent\Collection $products
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_buyxgety_rules
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_have_tags
 * @property \Illuminate\Database\Eloquent\Collection $outlets
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_product_discount_rules
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_promo_codes
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_reports
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_tier_discount_rules
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_user_filters
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaign extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign';

	protected $casts = [
		'created_by' => 'int',
		'last_updated_by' => 'int',
		'number_last_code' => 'int',
		'total_code' => 'int',
		'used_code' => 'int',
		'limitation_usage' => 'int'
	];

	protected $dates = [
		'date_start',
		'date_end'
	];

	protected $fillable = [
		'id_brand',
		'created_by',
		'last_updated_by',
		'campaign_name',
		'promo_title',
		'code_type',
		'prefix_code',
		'number_last_code',
		'total_coupon',
		'date_start',
		'date_end',
		'is_all_outlet',
		'promo_type',
		'user_type',
		'specific_user',
		'used_code',
		'limitation_usage',
		'step_complete',
        'charged_central',
        'charged_outlet',
        'export_date',
        'export_url',
        'export_status'
	];

	public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'created_by');
    }

	public function products()
	{
		return $this->belongsToMany(\App\Http\Models\Product::class, 'promo_campaign_tier_discount_products', 'id_promo_campaign', 'id_product')
					->withPivot('id_promo_campaign_product_discount_rule', 'id_product_category')
					->withTimestamps();
	}

	public function promo_campaign_buyxgety_rules()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyRule::class, 'id_promo_campaign');
	}

	public function promo_campaign_have_tags()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignHaveTag::class, 'id_promo_campaign');
	}

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'promo_campaign_outlets', 'id_promo_campaign', 'id_outlet')
					->withPivot('id_promo_campaign_outlet')
					->withTimestamps();
	}

	public function promo_campaign_outlets()
    {
        return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignOutlet::class, 'id_promo_campaign', 'id_promo_campaign');
    }

	public function promo_campaign_product_discount_rules()
	{
		return $this->hasOne(\Modules\PromoCampaign\Entities\PromoCampaignProductDiscountRule::class, 'id_promo_campaign');
	}

	public function promo_campaign_promo_codes()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignPromoCode::class, 'id_promo_campaign');
	}

	public function promo_campaign_reports()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignReport::class, 'id_promo_campaign');
	}

	public function promo_campaign_tier_discount_rules()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignTierDiscountRule::class, 'id_promo_campaign');
	}

	public function promo_campaign_user_filters()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignUserFilter::class, 'id_promo_campaign');
	}

	public function promo_campaign_buyxgety_product_requirement()
    {
        return $this->hasOne(\Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductRequirement::class, 'id_promo_campaign', 'id_promo_campaign');
    }

    public function promo_campaign_tier_discount_product()
    {
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignTierDiscountProduct::class, 'id_promo_campaign', 'id_promo_campaign');
    }

    public function promo_campaign_product_discount()
    {
        return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignProductDiscount::class, 'id_promo_campaign', 'id_promo_campaign');
    }

    public function promo_campaign_referral()
    {
        return $this->hasOne(\Modules\PromoCampaign\Entities\PromoCampaignReferral::class, 'id_promo_campaign', 'id_promo_campaign');
    }

    public function brand()
    {
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class,'id_brand');
	}

	public function getGetAllRulesAttribute() {
		$this->load([
			'promo_campaign_outlets',
			'promo_campaign_product_discount_rules',
			'promo_campaign_tier_discount_rules',
			'promo_campaign_buyxgety_rules',
			'promo_campaign_product_discount.product' => function($q) {
				$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
			},
			'promo_campaign_buyxgety_product_requirement.product' => function($q) {
				$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
			},
			'promo_campaign_tier_discount_product.product' => function($q) {
				$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
			},
			'promo_campaign_tier_discount_rules.product' => function($q) {
				$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
			},
			'brand'
		]);
	}
}
