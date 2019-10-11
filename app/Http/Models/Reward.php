<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $primaryKey = 'id_reward';

    protected $casts = [
		'id_reward' => 'int',
		'id_user' => 'int',
		'reward_coupon_point' => 'int'
	];

	protected $dates = [
		'reward_start',
		'reward_end'
	];

	protected $fillable = [
        'reward_name',
        'reward_image',
        'reward_description',
        'reward_coupon_point',
        'reward_start',
        'reward_end',
        'reward_publish_start',
        'reward_publish_end',
        'count_winner',
        'winner_type',
		'created_at',
		'updated_at'
	];

    protected $appends  = ['url_reward_image'];

	// ATTRIBUTE IMAGE URL
	public function getUrlRewardImageAttribute() {
		if (empty($this->reward_image)) {
            return env('S3_URL_API').'img/default.jpg';
        }
        else {
            return env('S3_URL_API').$this->reward_image;
        }
    }

	public function reward_user()
	{
		return $this->hasMany(\App\Http\Models\RewardUser::class, 'id_reward')->orderBy('total_coupon', 'DESC');
	}

	public function winner()
	{
		return $this->hasMany(\App\Http\Models\RewardUser::class, 'id_reward')->where('is_winner', '1');
	}

}
