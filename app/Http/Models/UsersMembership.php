<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersMembership
 * 
 * @property int $id_log_membership
 * @property int $id_user
 * @property int $id_membership
 * @property int $min_total_value
 * @property int $min_total_count
 * @property \Carbon\Carbon $retain_date
 * @property int $retain_min_total_value
 * @property int $retain_min_total_count
 * @property int $benefit_point_multiplier
 * @property int $benefit_cashback_multiplier
 * @property int $benefit_discount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 * @property \App\Http\Models\Membership $membership
 *
 * @package App\Models
 */
class UsersMembership extends Model
{
	protected $primaryKey = 'id_log_membership';
	public $incrementing = false;

	protected $casts = [
		'id_log_membership' => 'int',
		'id_user' => 'int',
		'id_membership' => 'int',
		'min_total_value' => 'int',
		'min_total_count' => 'int',
		'retain_min_total_value' => 'int',
		'retain_min_total_count' => 'int',
		'benefit_point_multiplier' => 'int',
		'benefit_cashback_multiplier' => 'int',
		'benefit_discount' => 'int',
		'cashback_maximum' => 'int'
	];

	protected $dates = [
		'retain_date'
	];

	protected $fillable = [
		'id_user',
		'id_membership',
		'membership_name',
		'membership_name_color',
		'membership_image',
		'min_total_value',
		'min_total_count',
		'retain_date',
		'retain_min_total_value',
		'retain_min_total_count',
		'benefit_point_multiplier',
		'benefit_cashback_multiplier',
		'benefit_promo_id',
		'benefit_discount',
		'cashback_maximum'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function membership()
	{
		return $this->belongsTo(\App\Http\Models\Membership::class, 'id_membership');
	}
}
