<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OvoReference extends Model
{
	protected $primaryKey = 'id_ovo_reference';

	protected $fillable = [
		'date',
		'batch_no',
		'reference_number',
		'created_at',
		'updated_at'
	];

}
