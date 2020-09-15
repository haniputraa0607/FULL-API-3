<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 15 Sep 2020 10:40:05 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignDiscountBillRule
 * 
 * @property int $id_promo_campaign_discount_bill_rule
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
class PromoCampaignDiscountBillRule extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_discount_bill_rule';

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
