<?php

namespace Modules\PromoCampaign\Entities;

use Illuminate\Database\Eloquent\Model;

class UserReferralCashback extends Model
{
	public $primaryKey = 'id_user_referral_cashback';
    protected $fillable = [
    	'id_user',
    	'referral_code',
    	'number_transaction',
    	'cashback_earned'
    ];
}
