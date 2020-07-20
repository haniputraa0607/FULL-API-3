<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:41:51 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignUserFilter
 * 
 * @property int $id_promo_campaign_user_filter
 * @property string $subject
 * @property string $operator
 * @property string $parameter
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $id_promo_campaign
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignUserFilter extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_user_filter';

	protected $casts = [
		'id_promo_campaign' => 'int'
	];

	protected $fillable = [
		'subject',
		'operator',
		'parameter',
		'id_promo_campaign'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
