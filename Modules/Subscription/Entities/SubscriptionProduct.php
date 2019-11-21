<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:34:50 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionProduct
 * 
 * @property int $id_subscription_product
 * @property int $id_subscription
 * @property int $id_product
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Product $product
 * @property \Modules\Subscription\Entities\Subscription $subscription
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionProduct extends Eloquent
{
	protected $primaryKey = 'id_subscription_product';

	protected $casts = [
		'id_subscription' => 'int',
		'id_product' => 'int'
	];

	protected $fillable = [
		'id_subscription',
		'id_product'
	];

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
