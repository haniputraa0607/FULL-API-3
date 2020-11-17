<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionBrand extends Model
{
	public $timestamps = false;
    protected $fillable = [
    	'id_brand',
    	'id_subscription'
    ];

    public function subscription()
	{
        return $this->belongsTo(\App\Http\Models\Deal::class, 'id_subscription', 'id_subscription');
	}
}
