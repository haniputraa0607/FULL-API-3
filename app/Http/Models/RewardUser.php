<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class RewardUser extends Model
{
    protected $primaryKey = 'id_reward_user';

    protected $casts = [
		'id_user' => 'int',
		'total_coupon' => 'int'
	];

	protected $fillable = [
        'id_reward',
        'id_user',
        'total_coupon',
        'is_winner',
		'created_at',
		'updated_at'
	];

	public function reward()
	{
		return $this->belongsTo(\App\Http\Models\Reward::class, 'id_reward');
	}
	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
