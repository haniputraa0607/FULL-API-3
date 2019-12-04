<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 03 Dec 2019 10:14:43 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionPaymentOvo
 * 
 * @property int $id_subscription_payment_ovo
 * @property int $id_subscription
 * @property int $amount
 * @property string $trace_number
 * @property string $approval_code
 * @property string $response_code
 * @property string $batch_no
 * @property string $phone
 * @property string $ovoid
 * @property int $cash_used
 * @property int $ovo_points_earned
 * @property int $cash_balance
 * @property int $full_name
 * @property int $ovo_points_used
 * @property int $ovo_points_balance
 * @property string $payment_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Subscription $subscription
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionPaymentOvo extends Eloquent
{
	protected $primaryKey = 'id_subscription_payment_ovo';

	protected $casts = [
		'id_subscription' => 'int',
		'amount' => 'int',
		'cash_used' => 'int',
		'ovo_points_earned' => 'int',
		'cash_balance' => 'int',
		'full_name' => 'int',
		'ovo_points_used' => 'int',
		'ovo_points_balance' => 'int'
	];

	protected $fillable = [
		'id_subscription',
		'amount',
		'trace_number',
		'approval_code',
		'response_code',
		'batch_no',
		'phone',
		'ovoid',
		'cash_used',
		'ovo_points_earned',
		'cash_balance',
		'full_name',
		'ovo_points_used',
		'ovo_points_balance',
		'payment_type'
	];

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
