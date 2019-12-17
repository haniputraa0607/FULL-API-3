<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:43:20 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCodeWording
 * 
 * @property int $id_promo_code_wording
 * @property string $promo_type
 * @property string $wording
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCodeWording extends Eloquent
{
	protected $table = 'promo_code_wording';
	protected $primaryKey = 'id_promo_code_wording';

	protected $fillable = [
		'promo_type',
		'wording'
	];
}
