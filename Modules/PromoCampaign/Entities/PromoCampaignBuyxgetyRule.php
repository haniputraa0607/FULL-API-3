<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:39:40 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignBuyxgetyRule
 * 
 * @property int $id_promo_campaign_buyxgety_rule
 * @property int $id_promo_campaign
 * @property int $min_qty_requirement
 * @property int $max_qty_requirement
 * @property int $benefit_id_product
 * @property int $benefit_qty
 * @property int $discount_percent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\Product $product
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignBuyxgetyRule extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_buyxgety_rule';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'min_qty_requirement' => 'int',
		'max_qty_requirement' => 'int',
		'benefit_id_product' => 'int',
		'benefit_qty' => 'int',
		'discount_percent' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'min_qty_requirement',
		'max_qty_requirement',
		'benefit_id_product',
		'benefit_qty',
		'discount_percent'
	];

	public function product()
	{
		return $this->belongsTo(\App\Models\Product::class, 'benefit_id_product');
	}

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
