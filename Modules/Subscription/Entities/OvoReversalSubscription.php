<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class OvoReversalSubscription extends Model
{
    protected $fillable = [
		'id_subscription_user',
		'id_subscription_payment_ovo',
		'date_push_to_pay',
		'request',
		'created_at',
		'updated_at'
	];
}
