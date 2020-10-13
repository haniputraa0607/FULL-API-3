<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 15 Sep 2020 15:45:27 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsDiscountBillRule
 * 
 * @property int $id_deals_discount_bill_rule
 * @property int $id_deals
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
class DealsDiscountBillRule extends Eloquent
{
	protected $primaryKey = 'id_deals_discount_bill_rule';

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

	public function deal()
	{
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}
}
