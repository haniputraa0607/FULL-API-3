<?php

namespace Modules\PromoCampaign\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoCampaignReferral extends Model
{
    protected $fillable = [];
    public function promo_campaign() {
    	return $this->belongsTo(PromoCampaign::class,'id_promo_campaign','id_promo_campaign');
    }
}
