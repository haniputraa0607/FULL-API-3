<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 13 Oct 2020 14:17:08 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPaymentMethod
 * 
 * @property int $id_deals_payment_method
 * @property int $id_deals
 * @property string $payment_method_code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Deals\Entities\Deal $deal
 *
 * @package Modules\Deals\Entities
 */
class DealsPaymentMethod extends Eloquent
{
	protected $primaryKey = 'id_deals_payment_method';

	protected $casts = [
		'id_deals' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'payment_method'
	];

	public function deal()
	{
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}
}
