<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionSchedule extends Model
{
	protected $primaryKey = 'id_promotion_schedule';

	protected $casts = [
		'id_promotion' => 'int',
	];

	protected $fillable = [
		'id_promotion',
		'schedule_time',
		'schedule_exact_date',
		'schedule_date_month',
		'schedule_date_every_month',
		'schedule_day_every_week',
		'schedule_week_in_month',
		'schedule_everyday',
		'date_start',
		'date_end',
		'created_at',
		'updated_at',
	];

	public function promotion()
	{
		return $this->belongsTo(\App\Http\Models\Promotion::class, 'id_promotion');
	}
}
