<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:39:50 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignHaveTag
 * 
 * @property int $id_promo_campaign_have_tag
 * @property int $id_promo_campaign_tag
 * @property int $id_promo_campaign
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 * @property \Modules\PromoCampaign\Entities\PromoCampaignTag $promo_campaign_tag
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignHaveTag extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_have_tag';

	protected $casts = [
		'id_promo_campaign_tag' => 'int',
		'id_promo_campaign' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign_tag',
		'id_promo_campaign'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}

	public function promo_campaign_tag()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignTag::class, 'id_promo_campaign_tag');
	}
}
