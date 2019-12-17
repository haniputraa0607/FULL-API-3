<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:41:26 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignTierDiscountProduct
 * 
 * @property int $id_promo_campaign_product_discount_rule
 * @property int $id_promo_campaign
 * @property int $id_product
 * @property int $id_product_category
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\Product $product
 * @property \Modules\PromoCampaign\Entities\ProductCategory $product_category
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignTierDiscountProduct extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_product_discount_rule';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'id_product' => 'int',
		'id_product_category' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'id_product',
		'id_product_category'
	];

	public function product()
	{
		return $this->belongsTo(\App\Models\Product::class, 'id_product');
	}

	public function product_category()
	{
		return $this->belongsTo(\App\Models\ProductCategory::class, 'id_product_category');
	}

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
