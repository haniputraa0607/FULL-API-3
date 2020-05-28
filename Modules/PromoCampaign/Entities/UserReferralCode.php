<?php

namespace Modules\PromoCampaign\Entities;

use Illuminate\Database\Eloquent\Model;

class UserReferralCode extends Model
{
	public $primaryKey = 'id_user';
    protected $fillable = [
    	'id_promo_campaign_promo_code',
    	'id_user',
    	'number_transaction',
    	'cashback_earned'
    ];
}
