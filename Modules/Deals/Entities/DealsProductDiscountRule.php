<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 04 Feb 2020 14:41:09 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsProductDiscountRule
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
 * @property \Modules\Deals\Entities\Deal $deal
 *
 * @package Modules\Deals\Entities
 */
class DealsProductDiscountRule extends Eloquent
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

	public function deal()
	{
		return $this->belongsTo(\App\Models\Deal::class, 'id_deals');
	}
}
