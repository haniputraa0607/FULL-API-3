<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OutletSchedule extends Model
{
    protected $primaryKey = 'id_outlet_schedule';

    protected $fillable = [
		'id_outlet',
		'day',
		'open',
		'close',
		'created_at',
		'updated_at',
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
}
