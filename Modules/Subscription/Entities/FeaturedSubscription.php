<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:36:11 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class FeaturedSubscription
 * 
 * @property int $id_featured_subscription
 * @property int $id_subscription
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Subscription $subscription
 *
 * @package Modules\Subscription\Entities
 */
class FeaturedSubscription extends Eloquent
{
	protected $primaryKey = 'id_featured_subscription';

	protected $casts = [
		'id_subscription' => 'int',
		'order' => 'int'
	];

	protected $dates = [
		'date_start',
		'date_end'
	];

	protected $fillable = [
		'id_subscription',
		'date_start',
		'date_end',
		'order'
	];

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
