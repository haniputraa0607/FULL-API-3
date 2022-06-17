<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 14 Sep 2021 10:44:11 +0700.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HairstylistScheduleDate
 * 
 * @property int $id_hairstylist_schedule_date
 * @property int $id_hairstylist_schedule
 * @property \Carbon\Carbon $date
 * @property string $shift
 * @property string $request_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistScheduleDate extends Model
{
	protected $primaryKey = 'id_hairstylist_schedule_date';

	protected $casts = [
		'id_hairstylist_schedule' => 'int'
	];

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_hairstylist_schedule',
		'date',
		'shift',
		'request_by',
		'id_outlet_box',
		'id_hairstylist_attendance',
		'is_overtime',
		'time_start',
		'time_end',
		'notes',
	];

	public function hairstylist_schedule()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\HairstylistSchedule::class, 'id_hairstylist_schedule');
	}
}
