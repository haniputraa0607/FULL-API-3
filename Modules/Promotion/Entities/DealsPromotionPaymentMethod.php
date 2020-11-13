<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 13 Oct 2020 14:17:23 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionPaymentMethod
 * 
 * @property int $id_deals_promotion_payment_method
 * @property int $id_deals
 * @property string $payment_method
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Promotion\Entities\DealsPromotionTemplate $deals_promotion_template
 *
 * @package Modules\Promotion\Entities
 */
class DealsPromotionPaymentMethod extends Eloquent
{
	protected $primaryKey = 'id_deals_promotion_payment_method';

	protected $casts = [
		'id_deals' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'payment_method'
	];

	public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}
}
