<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 16 Sep 2020 15:41:51 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignDelivery
 * 
 * @property int $id_promo_campaign_delivery
 * @property int $id_promo_campaign
 * @property string $delivery
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignDelivery extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_delivery';

	protected $casts = [
		'id_promo_campaign' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'delivery'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
