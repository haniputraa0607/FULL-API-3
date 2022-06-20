<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 14 Sep 2021 10:44:38 +0700.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Outlet\Entities\OutletTimeShift;

/**
 * Class HairstylistSchedule
 * 
 * @property int $id_hairstylist_schedule
 * @property int $id_user_hair_stylist
 * @property int $id_outlet
 * @property int $approve_by
 * @property \Carbon\Carbon $request_at
 * @property \Carbon\Carbon $approve_at
 * @property \Carbon\Carbon $reject_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistSchedule extends Model
{
	protected $primaryKey = 'id_hairstylist_schedule';

	protected $casts = [
		'id_user_hair_stylist' => 'int',
		'id_outlet' => 'int',
		'approve_by' => 'int'
	];

	protected $dates = [
		'request_at',
		'approve_at',
		'reject_at'
	];

	protected $fillable = [
		'id_user_hair_stylist',
		'id_outlet',
		'approve_by',
		'last_updated_by',
		'schedule_month',
		'schedule_year',
		'request_at',
		'approve_at',
		'reject_at'
	];

	public function hairstylist_schedule_dates()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistScheduleDate::class, 'id_hairstylist_schedule');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}

	public function refreshTimeShift()
	{
		$timeShift = OutletTimeShift::where('outlet_time_shift.id_outlet', $this->id_outlet)->join('outlet_schedules', 'outlet_schedules.id_outlet_schedule', 'outlet_time_shift.id_outlet_schedule')->get();
		$schedules = [];
		$oneDay = [
			'senin' => '01',
			'selasa' => '02',
			'rabu' => '03',
			'kamis' => '04',
			'jumat' => '05',
			'jum\'at' => '05',
			'sabtu' => '06',
			'minggu' => '07',
			'monday' => '01',
			'tuesday' => '02',
			'wednesday' => '03',
			'thursday' => '04',
			'friday' => '05',
			'saturday' => '06',
			'sunday' => '07',
		];
		$timeShift->each(function ($item) use (&$schedules, $oneDay) {
			$daycode = $oneDay[strtolower($item->day)] ?? $item->day;
			if (!isset($schedules[$daycode])) {
				$schedules[$daycode] = [];
			}
			$schedules[$daycode][$item->shift] = [
				'time_start' => $item->shift_time_start,
				'time_end' => $item->shift_time_end,
			];
		});

		$this->hairstylist_schedule_dates->each(function ($item) use ($schedules, $oneDay) {
			$daycode = $oneDay[strtolower(date('l', strtotime($item->date)))];
			$item->update([
				'time_start' => $schedules[$daycode][$item->shift]['time_start'] ?? '00:00:00',
				'time_end' => $schedules[$daycode][$item->shift]['time_end'] ?? '00:00:00',
			]);
		});

		return true;
	}
}
