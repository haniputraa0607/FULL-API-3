<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:41:39 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignTierDiscountRule
 * 
 * @property int $id_promo_campaign_tier_discount_rule
 * @property int $id_promo_campaign
 * @property int $min_qty
 * @property int $max_qty
 * @property string $discount_type
 * @property int $discount_value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignTierDiscountRule extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_tier_discount_rule';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'min_qty' => 'int',
		'max_qty' => 'int',
		'discount_value' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'min_qty',
		'max_qty',
		'discount_type',
		'discount_value'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
