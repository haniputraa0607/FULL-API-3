<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LogPoint
 * 
 * @property int $id_log_point
 * @property int $id_user
 * @property int $point
 * @property int $id_reference
 * @property string $source
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class LogPoint extends Model
{
	protected $primaryKey = 'id_log_point';

	protected $casts = [
		'id_user' => 'int',
		'point' => 'int',
		'id_reference' => 'int'
	];

	protected $fillable = [
		'id_user',
		'point',
		'id_reference',
		'source',
		'voucher_price',
		'grand_total',
		'point_conversion',
		'membership_level',
		'membership_point_percentage',
		'reward_coupon_point',
		'reward_total_coupon'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_reference');
	}
}
