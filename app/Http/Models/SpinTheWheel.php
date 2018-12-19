<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SpinTheWheel extends Model
{
    protected $primaryKey = 'id_spin_the_wheel';

	protected $fillable = [
		'id_deals',
		'value'
	];

	public function deals()
	{
		return $this->hasOne(\App\Http\Models\Deal::class, 'id_deals', 'id_deals');
	}
}
