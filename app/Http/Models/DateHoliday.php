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
class DateHoliday extends Model
{
	protected $primaryKey = 'id_date_holiday';

	protected $fillable = [
		'id_holiday',
		'date'
	];

	public function holiday()
	{
		return $this->belongsTo(\App\Http\Models\Holiday::class, 'id_holiday', 'id_holiday');
	}
}
