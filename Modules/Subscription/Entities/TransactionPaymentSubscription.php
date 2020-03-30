<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 24 Mar 2020 13:19:34 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class TransactionPaymentSubscription
 * 
 * @property int $id_transaction_payment_subscription
 * @property int $id_transaction
 * @property int $id_subscription_user_voucher
 * @property int $subscription_nominal
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\SubscriptionUserVoucher $subscription_user_voucher
 * @property \App\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionPaymentSubscription extends Eloquent
{
	protected $primaryKey = 'id_transaction_payment_subscription';

	protected $casts = [
		'id_transaction' => 'int',
		'id_subscription_user_voucher' => 'int',
		'subscription_nominal' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'id_subscription_user_voucher',
		'subscription_nominal'
	];

	public function subscription_user_voucher()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\SubscriptionUserVoucher::class, 'id_subscription_user_voucher');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}
}
