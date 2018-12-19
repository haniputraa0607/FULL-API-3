<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardCard extends Model
{
    protected $primaryKey = 'id_dashboard_card';

	protected $fillable = [
		'id_dashboard_user',
		'card_name',
		'card_order',
		'created_at',
		'updated_at'
	];

	public function dashboard_user()
	{
		return $this->belongsTo(\App\Http\Models\DashboardUser::class, 'id_dashboard_user');
	}
}
