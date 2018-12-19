<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardDateRange extends Model
{
    protected $primaryKey = 'id_dashboard_date_range';

	protected $fillable = [
		'id_user',
		'default_date_range',
		'created_at',
		'updated_at'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
