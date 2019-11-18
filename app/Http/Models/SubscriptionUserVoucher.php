<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:35:11 +0700.
 */

namespace App\Http\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionUserVoucher
 * 
 * @property int $id_subscription_user_voucher
 * @property int $id_subscription_user
 * @property string $voucher_code
 * @property \Carbon\Carbon $used_at
 * @property int $id_transaction
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\SubscriptionUser $subscription_user
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Http\Models
 */
class SubscriptionUserVoucher extends Eloquent
{
	protected $primaryKey = 'id_subscription_user_voucher';

	protected $casts = [
		'id_subscription_user' => 'int',
		'id_transaction' => 'int'
	];

	protected $dates = [
		'used_at'
	];

	protected $fillable = [
		'id_subscription_user',
		'voucher_code',
		'used_at',
		'id_transaction'
	];

	public function subscription_user()
	{
		return $this->belongsTo(\App\Http\Models\SubscriptionUser::class, 'id_subscription_user');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}
}
