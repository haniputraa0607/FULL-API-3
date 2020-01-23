<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:40:05 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignOutlet
 * 
 * @property int $id_promo_campaign_outlet
 * @property int $id_promo_campaign
 * @property int $id_outlet
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\PromoCampaign\Entities\Outlet $outlet
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignOutlet extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_outlet';

	protected $casts = [
		'id_promo_campaign' => 'int',
		'id_outlet' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign',
		'id_outlet'
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}
}
