<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 19 Nov 2019 09:30:16 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionPaymentMidtran
 * 
 * @property int $id_subscription_payment
 * @property int $id_subscription
 * @property string $masked_card
 * @property string $approval_code
 * @property string $bank
 * @property string $eci
 * @property string $transaction_time
 * @property string $gross_amount
 * @property string $order_id
 * @property string $payment_type
 * @property string $signature_key
 * @property string $status_code
 * @property string $vt_transaction_id
 * @property string $transaction_status
 * @property string $fraud_status
 * @property string $status_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Subscription\Entities\Subscription $subscription
 *
 * @package Modules\Subscription\Entities
 */
class SubscriptionPaymentMidtran extends Eloquent
{
	protected $primaryKey = 'id_subscription_payment';

	protected $casts = [
		'id_subscription' => 'int'
	];

	protected $fillable = [
		'id_subscription',
		'id_subscription_user',
		'masked_card',
		'approval_code',
		'bank',
		'eci',
		'transaction_time',
		'gross_amount',
		'order_id',
		'payment_type',
		'signature_key',
		'status_code',
		'vt_transaction_id',
		'transaction_status',
		'fraud_status',
		'status_message'
	];

	public function subscription()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
	}
}
