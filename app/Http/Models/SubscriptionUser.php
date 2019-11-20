<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 15 Nov 2019 14:34:57 +0700.
 */

namespace App\Http\Models;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionUser
 * 
 * @property int $id_subscription_user
 * @property int $id_user
 * @property int $id_subscription
 * @property \Carbon\Carbon $bought_at
 * @property \Carbon\Carbon $subscription_expired_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Subscription $subscription
 * @property \App\Http\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection $subscription_user_vouchers
 *
 * @package App\Http\Models
 */
class SubscriptionUser extends Eloquent
{
	protected $primaryKey = 'id_subscription_user';

	protected $casts = [
		'id_user' => 'int',
		'id_subscription' => 'int'
	];

	protected $dates = [
		'bought_at',
		'subscription_expired_at'
	];

	protected $fillable = [
		'id_user',
		'id_subscription',
		'bought_at',
		'subscription_expired_at',
		'subscription_price_point',
		'subscription_price_cash',
		'payment_method',
		'paid_status'
	];

	public function subscription()
	{
		return $this->belongsTo(\App\Http\Models\Subscription::class, 'id_subscription');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function subscription_user_vouchers()
	{
		return $this->hasMany(\App\Http\Models\SubscriptionUserVoucher::class, 'id_subscription_user');
	}
}
