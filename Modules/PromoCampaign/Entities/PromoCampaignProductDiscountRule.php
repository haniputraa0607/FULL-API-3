<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:40:27 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignProductDiscountRule
 * 
 * @property int $id_promo_campaign_product_discount_rule
 * @property int $id_promo_campaign
 * @property string $is_all_product
 * @property string $discount_type
 * @property int $discount_value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignProductDiscountRule extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_product_discount_rule';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'discount_value' => 'int',
		'max_percent_discount' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'is_all_product',
		'discount_type',
		'discount_value',
		'max_product',
		'max_percent_discount'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
