<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DealsOutlet
 * 
 * @property int $id_deals
 * @property int $id_outlet
 * 
 * @property \App\Http\Models\Deal $deal
 * @property \App\Http\Models\Outlet $outlet
 *
 * @package App\Models
 */
class DealsOutlet extends Model
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id_deals' => 'int',
		'id_outlet' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'id_outlet'
	];

	public function deal()
	{
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
}
