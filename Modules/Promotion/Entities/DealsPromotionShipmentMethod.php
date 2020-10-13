<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 12 Oct 2020 15:49:20 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionShipmentMethod
 * 
 * @property int $id_deals_promotion_shipment_method
 * @property int $id_deals
 * @property string $shipment_method
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Promotion\Entities\DealsPromotionTemplate $deals_promotion_template
 *
 * @package Modules\Promotion\Entities
 */
class DealsPromotionShipmentMethod extends Eloquent
{
	protected $primaryKey = 'id_deals_promotion_shipment_method';

	protected $casts = [
		'id_deals' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'shipment_method'
	];

	public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}
}
