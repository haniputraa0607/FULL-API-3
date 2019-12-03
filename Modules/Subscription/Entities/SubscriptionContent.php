<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 03 Dec 2019 10:14:14 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionContent
 * 
 * @property int $id_subscription_content
 * @property int $id_subscription
 * @property string $title
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Subscription $subscription
 * @property \Illuminate\Database\Eloquent\Collection $subscription_content_details
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionContent extends Eloquent
{
	protected $primaryKey = 'id_subscription_content';

	protected $casts = [
		'id_subscription' => 'int',
		'order' => 'int',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'id_subscription',
		'title',
		'order',
		'is_active'
	];

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}

	public function subscription_content_details()
	{
		return $this->hasMany(\Modules\Subscription\Entities\SubscriptionContentDetail::class, 'id_subscription_content');
	}
}
