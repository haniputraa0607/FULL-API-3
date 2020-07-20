<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFraud extends Model
{
    protected $primaryKey = 'id_user_fraud';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $fillable = [
		'id_user',
		'device_type',
		'device_id'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
