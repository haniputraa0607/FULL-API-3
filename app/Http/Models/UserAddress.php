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
class UserAddress extends Model
{
	protected $primaryKey = 'id_user_address';

	protected $casts = [
		'id_user' => 'int',
		'id_city' => 'int'
	];

	protected $fillable = [
		'name',
		'phone',
		'id_user',
		'id_city',
		'address',
		'postal_code',
		'description',
		'primary'
	];

	public function city()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'id_city');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
