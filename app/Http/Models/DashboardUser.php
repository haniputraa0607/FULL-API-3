<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardUser extends Model
{
    protected $primaryKey = 'id_dashboard_user';

	protected $fillable = [
		'id_user',
		'section_title',
		'section_order',
		'created_at',
		'updated_at'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id');
	}

	public function dashboard_card()
	{
		return $this->hasMany(\App\Http\Models\DashboardCard::class, 'id_dashboard_user')->orderBy('card_order', 'ASC');
	}

}
