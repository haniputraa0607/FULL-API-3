<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 17 Sep 2020 11:38:38 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionDiscountDeliveryRule
 * 
 * @property int $id_deals_promotion_discount_delivery_rule
 * @property int $id_deals
 * @property string $discount_type
 * @property int $discount_value
 * @property int $max_percent_discount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Promotion\Entities\DealsPromotionTemplate $deals_promotion_template
 *
 * @package Modules\Promotion\Entities
 */
class DealsPromotionDiscountDeliveryRule extends Eloquent
{
	protected $primaryKey = 'id_deals_promotion_discount_delivery_rule';

	protected $casts = [
		'id_deals' => 'int',
		'discount_value' => 'int',
		'max_percent_discount' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'discount_type',
		'discount_value',
		'max_percent_discount'
	];

	public function deals_promotion_template()
	{
		return $this->belongsTo(\Modules\Promotion\Entities\DealsPromotionTemplate::class, 'id_deals');
	}
}
