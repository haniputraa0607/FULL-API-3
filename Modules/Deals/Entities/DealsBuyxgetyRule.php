<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 04 Feb 2020 14:40:44 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsBuyxgetyRule
 * 
 * @property int $id_deals_buyxgety_rule
 * @property int $id_deals
 * @property int $min_qty_requirement
 * @property int $max_qty_requirement
 * @property int $benefit_id_product
 * @property int $benefit_qty
 * @property int $discount_type
 * @property int $discount_value
 * @property int $max_percent_discount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Deals\Entities\Product $product
 * @property \Modules\Deals\Entities\Deal $deal
 *
 * @package Modules\Deals\Entities
 */
class DealsBuyxgetyRule extends Eloquent
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
		'max_percent_discount'
	];

	public function product()
	{
		return $this->belongsTo(\App\Models\Product::class, 'benefit_id_product');
	}

	public function deal()
	{
		return $this->belongsTo(\App\Models\Deal::class, 'id_deals');
	}
}
