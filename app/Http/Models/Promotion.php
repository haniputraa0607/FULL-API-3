<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
	protected $primaryKey = 'id_promotion';

	protected $casts = [
		'id_user' => 'int',
	];

	protected $fillable = [
		'promotion_name',
		'id_user',
		'promotion_type',
		'promotion_vouchers',
		'promotion_series',
		'promotion_queue_priority',
		'promotion_user_limit',
		'created_at',
		'updated_at',
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function promotion_rule_parents()
	{
		return $this->hasMany(\App\Http\Models\PromotionRuleParent::class, 'id_promotion')
					->select('id_promotion_rule_parent', 'id_promotion', 'promotion_rule as rule', 'promotion_rule_next as rule_next');
	}

	public function schedules()
	{
		return $this->hasMany(\App\Http\Models\PromotionSchedule::class, 'id_promotion');
	}

	public function contents()
	{
		return $this->hasMany(\App\Http\Models\PromotionContent::class, 'id_promotion');
	}

	public function queues()
	{
		return $this->hasMany(\App\Http\Models\PromotionQueue::class, 'id_promotion');
	}
	
	/* public function user_inboxes()
	{
		return $this->hasMany(\App\Http\Models\UserInbox::class, 'id_promotion');
	} */
}
