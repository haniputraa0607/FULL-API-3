<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 03 Dec 2019 10:14:28 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionContentDetail
 * 
 * @property int $id_subscription_content_detail
 * @property int $id_subscription_content
 * @property string $content
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\SubscriptionContent $subscription_content
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionContentDetail extends Eloquent
{
	protected $primaryKey = 'id_subscription_content_detail';

	protected $casts = [
		'id_subscription_content' => 'int',
		'order' => 'int'
	];

	protected $fillable = [
		'id_subscription_content',
		'content',
		'order'
	];

	public function subscription_content()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\SubscriptionContent::class, 'id_subscription_content');
	}
}
