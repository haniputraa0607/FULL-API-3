<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 07 Oct 2020 17:00:02 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignShipmentMethod
 * 
 * @property int $id_promo_campaign_shipment_method
 * @property int $id_promo_campaign
 * @property string $shipment_method
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignShipmentMethod extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_shipment_method';

	protected $casts = [
		'id_promo_campaign' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'shipment_method'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
