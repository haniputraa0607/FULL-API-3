<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:41:00 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignReport
 * 
 * @property int $id_promo_campaign_report
 * @property int $id_promo_campaign_promo_code
 * @property int $id_user
 * @property int $id_transaction
 * @property int $id_outlet
 * @property string $device_id
 * @property string $device_type
 * @property string $user_name
 * @property string $user_phone
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $id_promo_campaign
 * 
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 * @property \Modules\PromoCampaign\Entities\Outlet $outlet
 * @property \Modules\PromoCampaign\Entities\PromoCampaignPromoCode $promo_campaign_promo_code
 * @property \Modules\PromoCampaign\Entities\Transaction $transaction
 * @property \Modules\PromoCampaign\Entities\User $user
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignReport extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_report';

	protected $casts = [
		'id_promo_campaign_promo_code' => 'int',
		'id_user' => 'int',
		'id_transaction' => 'int',
		'id_outlet' => 'int',
		'id_promo_campaign' => 'int'
	];

	protected $fillable = [
		'id_promo_campaign_promo_code',
		'id_user',
		'id_transaction',
		'id_outlet',
		'device_id',
		'device_type',
		'user_name',
		'user_phone',
		'id_promo_campaign'
	];

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Models\Outlet::class, 'id_outlet');
	}

	public function promo_campaign_promo_code()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignPromoCode::class, 'id_promo_campaign_promo_code');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Models\Transaction::class, 'id_transaction');
	}

	public function user()
	{
		return $this->belongsTo(\App\Models\User::class, 'id_user');
	}
}
