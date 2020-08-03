<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 04 Mar 2020 16:18:01 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionTierDiscountRule
 * 
 * @property int $id_deals_tier_discount_rule
 * @property int $id_deals
 * @property int $min_qty
 * @property int $max_qty
 * @property string $discount_type
 * @property int $discount_value
 * @property int $max_percent_discount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\DealsPromotionTemplate $deals_promotion_template
 *
 * @package App\Models
 */
class DealsPromotionTierDiscountRule extends Eloquent
{
	protected $primaryKey = 'id_deals_tier_discount_rule';

	protected $casts = [
		'id_deals' => 'int',
		'min_qty' => 'int',
		'max_qty' => 'int',
		'discount_value' => 'int',
		'max_percent_discount' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'min_qty',
		'max_qty',
		'discount_type',
		'discount_value',
		'max_percent_discount'
	];

	public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}
}
