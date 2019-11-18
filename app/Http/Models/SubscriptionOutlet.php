<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:34:33 +0700.
 */

namespace App\Http\Models;

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
 * @property \App\Http\Models\Outlet $outlet
 * @property \App\Http\Models\Subscription $subscription
 *
 * @package App\Http\Models
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
		return $this->belongsTo(\App\Http\Models\Subscription::class, 'id_subscription');
	}
}
