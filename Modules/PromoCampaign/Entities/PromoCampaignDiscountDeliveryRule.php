<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 16 Sep 2020 15:41:29 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignDiscountDeliveryRule
 * 
 * @property int $id_promo_campaign_discount_delivery_rule
 * @property int $id_promo_campaign
 * @property string $discount_type
 * @property int $discount_value
 * @property int $max_percent_discount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignDiscountDeliveryRule extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_discount_delivery_rule';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'discount_value' => 'int',
		'max_percent_discount' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'discount_type',
		'discount_value',
		'max_percent_discount'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
