<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:40:41 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignPromoCode
 * 
 * @property int $id_promo_campaign_promo_code
 * @property int $id_promo_campaign
 * @property string $promo_code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $usage
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_reports
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignPromoCode extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_promo_code';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'usage' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'promo_code',
		'usage'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}

	public function promo_campaign_reports()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignReport::class, 'id_promo_campaign_promo_code');
	}
}
