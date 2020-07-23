<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 21 Jul 2020 15:26:24 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionWelcome
 * 
 * @property int $id_subscription_welcome
 * @property int $id_subscription
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\Subscription $subscription
 *
 * @package App\Models
 */
class SubscriptionWelcome extends Eloquent
{
	protected $primaryKey = 'id_subscription_welcome';

	protected $casts = [
		'id_subscription' => 'int'
	];

	protected $fillable = [
		'id_subscription'
	];

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
