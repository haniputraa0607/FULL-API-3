<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TopupNominal extends Model
{
    protected $primaryKey = 'id_topup_nominal';

	protected $fillable = [
		'type',
		'nominal_bayar',
		'nominal_topup',
		'created_at',
		'updated_at',
	];

	protected $hidden = [
		'type',
		'created_at',
		'updated_at',
	];
}
