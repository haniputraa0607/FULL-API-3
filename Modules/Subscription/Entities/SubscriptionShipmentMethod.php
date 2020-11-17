<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 15 Oct 2020 14:55:51 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionShipmentMethod
 * 
 * @property int $id_subscription_shipment_method
 * @property int $id_subscription
 * @property string $shipment_method
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Subscription $subscription
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionShipmentMethod extends Eloquent
{
	protected $primaryKey = 'id_subscription_shipment_method';

	protected $casts = [
		'id_subscription' => 'int'
	];

	protected $fillable = [
		'id_subscription',
		'shipment_method'
	];

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
