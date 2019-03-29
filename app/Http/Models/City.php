<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class City
 * 
 * @property int $id_city
 * @property int $id_province
 * @property string $city_name
 * @property string $city_type
 * @property string $city_postal_code
 * 
 * @property \App\Http\Models\Province $province
 * @property \Illuminate\Database\Eloquent\Collection $outlets
 * @property \Illuminate\Database\Eloquent\Collection $transaction_shipments
 * @property \Illuminate\Database\Eloquent\Collection $user_addresses
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */
class City extends Model
{
	protected $primaryKey = 'id_city';
	public $timestamps = false;

	protected $casts = [
		'id_province' => 'int'
	];

	protected $fillable = [
		'id_province',
		'city_name',
		'city_type',
		'city_postal_code',
		'city_latitude',
		'city_longitude'
	];

	public function province()
	{
		return $this->belongsTo(\App\Http\Models\Province::class, 'id_province');
	}

	public function outlets()
	{
		return $this->hasMany(\App\Http\Models\Outlet::class, 'id_city');
	}

	public function transaction_shipments()
	{
		return $this->hasMany(\App\Http\Models\TransactionShipment::class, 'destination_id_city');
	}

	public function user_addresses()
	{
		return $this->hasMany(\App\Http\Models\UserAddress::class, 'id_city');
	}

	public function users()
	{
		return $this->hasMany(\App\Http\Models\User::class, 'id_city');
	}
}
