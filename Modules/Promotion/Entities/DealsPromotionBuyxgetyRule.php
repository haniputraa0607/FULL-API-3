<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 04 Mar 2020 16:16:29 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionBuyxgetyRule
 * 
 * @property int $id_deals_buyxgety_rule
 * @property int $id_deals
 * @property int $min_qty_requirement
 * @property int $max_qty_requirement
 * @property int $benefit_id_product
 * @property int $benefit_qty
 * @property string $discount_type
 * @property int $discount_value
 * @property int $max_percent_discount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\Product $product
 * @property \App\Models\DealsPromotionTemplate $deals_promotion_template
 *
 * @package App\Models
 */
class DealsPromotionBuyxgetyRule extends Eloquent
{
	protected $primaryKey = 'id_deals_buyxgety_rule';

	protected $casts = [
		'id_deals' => 'int',
		'min_qty_requirement' => 'int',
		'max_qty_requirement' => 'int',
		'benefit_id_product' => 'int',
		'benefit_qty' => 'int',
		'discount_value' => 'int',
		'max_percent_discount' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'min_qty_requirement',
		'max_qty_requirement',
		'benefit_id_product',
		'benefit_qty',
		'discount_type',
		'discount_value',
		'max_percent_discount',
		'id_product_variant_group',
		'id_brand'
	];

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'benefit_id_product');
	}

	public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}

	public function brand()
	{
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class, 'id_brand');
	}
}
