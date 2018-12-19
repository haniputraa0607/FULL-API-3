<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Holiday
 * 
 * @property int $id_holiday
 * @property string $holiday_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection $outlets
 *
 * @package App\Models
 */
class Holiday extends Model
{
	protected $primaryKey = 'id_holiday';

	protected $fillable = [
		'holiday_name',
		'yearly'
	];

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'outlet_holidays', 'id_holiday', 'id_outlet')
					->withTimestamps();
	}

	public function outlet_holidays()
	{
		return $this->hasMany(\App\Http\Models\OutletHoliday::class, 'id_holiday', 'id_holiday');
	}

	public function date_holidays()
	{
		return $this->hasMany(\App\Http\Models\DateHoliday::class, 'id_holiday', 'id_holiday');
	}
}
