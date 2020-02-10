<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 04 Feb 2020 14:41:39 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsTierDiscountRule
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
 * @property \Modules\Deals\Entities\Deal $deal
 *
 * @package Modules\Deals\Entities
 */
class DealsTierDiscountRule extends Eloquent
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

	public function deal()
	{
		return $this->belongsTo(\App\Models\Deal::class, 'id_deals');
	}
}
