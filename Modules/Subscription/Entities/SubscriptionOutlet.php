<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:34:33 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionOutlet
 * 
 * @property int $id_subscription_outlets
 * @property int $id_subscription
 * @property int $id_outlet
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Outlet $outlet
 * @property \Modules\Subscription\Entities\Subscription $subscription
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionOutlet extends Eloquent
{
	protected $primaryKey = 'id_subscription_outlets';

	protected $casts = [
		'id_subscription' => 'int',
		'id_outlet' => 'int'
	];

	protected $fillable = [
		'id_subscription',
		'id_outlet'
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
