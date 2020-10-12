<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 12 Oct 2020 15:31:45 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsShipmentMethod
 * 
 * @property int $id_deals_shipment_method
 * @property int $id_deals
 * @property string $shipment_method
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Deals\Entities\Deal $deal
 *
 * @package Modules\Deals\Entities
 */
class DealsShipmentMethod extends Eloquent
{
	protected $primaryKey = 'id_deals_shipment_method';

	protected $casts = [
		'id_deals' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'shipment_method'
	];

	public function deal()
	{
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}
}
