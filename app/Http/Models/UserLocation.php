<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocation extends Model
{
	protected $primaryKey = 'id_location';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $fillable = [
		'id_user',
		'action',
		'lat',
		'lng'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
