<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:17 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OutletHoliday
 * 
 * @property int $id_outlet
 * @property int $id_holiday
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Holiday $holiday
 * @property \App\Http\Models\Outlet $outlet
 *
 * @package App\Models
 */
class OutletHoliday extends Model
{
	public $incrementing = false;

	protected $casts = [
		'id_outlet' => 'int',
		'id_holiday' => 'int'
	];

	protected $fillable = [
		'id_outlet',
		'id_holiday'
	];

	public function holiday()
	{
		return $this->belongsTo(\App\Http\Models\Holiday::class, 'id_holiday');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
}
