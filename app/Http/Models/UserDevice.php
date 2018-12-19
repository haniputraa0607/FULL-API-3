<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserDevice
 * 
 * @property int $id_device_user
 * @property int $id_user
 * @property string $device_type
 * @property string $device_id
 * @property string $device_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class UserDevice extends Model
{
	protected $primaryKey = 'id_device_user';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $hidden = [
		'device_token'
	];

	protected $fillable = [
		'id_user',
		'device_type',
		'device_id',
		'device_token'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
