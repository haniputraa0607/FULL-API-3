<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 07 Oct 2020 15:35:01 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignPaymentMethod
 * 
 * @property int $id_promo_campaign_payment_method
 * @property int $id_promo_campaign
 * @property string $payment_method_code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignPaymentMethod extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_payment_method';

	protected $casts = [
		'id_promo_campaign' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'payment_method_code'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
