<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 04 Mar 2020 16:17:18 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionProductDiscountRule
 * 
 * @property int $id_deals_product_discount_rule
 * @property int $id_deals
 * @property string $is_all_product
 * @property string $discount_type
 * @property int $discount_value
 * @property int $max_percent_discount
 * @property int $max_product
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\DealsPromotionTemplate $deals_promotion_template
 *
 * @package App\Models
 */
class DealsPromotionProductDiscountRule extends Eloquent
{
	protected $primaryKey = 'id_deals_product_discount_rule';

	protected $casts = [
		'id_deals' => 'int',
		'discount_value' => 'int',
		'max_percent_discount' => 'int',
		'max_product' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'is_all_product',
		'discount_type',
		'discount_value',
		'max_percent_discount',
		'max_product'
	];

	public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}
}
