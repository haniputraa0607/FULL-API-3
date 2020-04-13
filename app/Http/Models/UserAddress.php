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
		'latitude'  => 'float',
		'longitude' => 'float'
	];

	protected $fillable = [
		'name',
		'id_user',
		'short_address',
		'address',
		'description',
		'latitude',
		'longitude',
		'favorite',
		'type'
	];

	public function city()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'id_city');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function getTypeAttribute($value)
	{
		return $value?:'';
	}

	public function getDescriptionAttribute($value)
	{
		return $value?:'';
	}
}
