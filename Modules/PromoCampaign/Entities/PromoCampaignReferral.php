<?php

namespace Modules\PromoCampaign\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoCampaignReferral extends Model
{
	protected $primaryKey = 'id_promo_campaign_referrals';
    protected $fillable = [
            'referred_promo_type',
            'referred_promo_unit',
            'referred_promo_value',
            'referred_min_value',
            'referred_promo_value_max',
            'referrer_promo_unit',
            'referrer_promo_value',
            'referrer_promo_value_max'
    ];
    public function promo_campaign() {
    	return $this->belongsTo(PromoCampaign::class,'id_promo_campaign','id_promo_campaign');
    }
}
