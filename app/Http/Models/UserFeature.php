<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserFeature
 * 
 * @property int $id_user
 * @property int $id_feature
 * 
 * @property \App\Http\Models\Feature $feature
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class UserFeature extends Model
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id_user' => 'int',
		'id_feature' => 'int'
	];

	protected $fillable=[
		'id_feature',
		'id_user'
	];

	public function feature()
	{
		return $this->belongsTo(\App\Http\Models\Feature::class, 'id_feature');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
