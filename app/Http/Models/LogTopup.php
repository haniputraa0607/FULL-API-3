<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAddress
 * 
 * @property int $id_user_address
 * @property string $name
 * @property string $phone
 * @property int $id_user
 * @property int $id_city
 * @property string $address
 * @property string $postal_code
 * @property string $description
 * @property string $primary
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\City $city
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class LogTopup extends Model
{
	protected $primaryKey = 'id_log_topup';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $fillable = [
		'receipt_number',
		'id_user',
		'balance_before',
		'nominal_bayar',
		'topup_value',
		'balance_after',
		'transaction_reference',
		'source',
		'payment_type',
		'topup_payment_status',
		'created_at',
		'updated_at'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
