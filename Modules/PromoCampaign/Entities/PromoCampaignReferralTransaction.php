<?php

namespace Modules\PromoCampaign\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoCampaignReferralTransaction extends Model
{
    protected $fillable = [
    	'id_promo_campaign_promo_code',
    	'id_user',
    	'id_referrer',
    	'id_transaction',
        'referred_bonus_type',
        'referred_bonus',
        'referrer_bonus'
    ];
    public function user()
    {
    	return $this->belongsTo(\App\Http\Models\User::class,'id_user');
    }
    public function referrer()
    {
    	return $this->belongsTo(\App\Http\Models\User::class,'id_user');
    }
    public function transaction()
    {
    	return $this->belongsTo(\App\Http\Models\Transaction::class,'id_transaction');
    }
}
