<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OvoResponseCode extends Model
{
	protected $primaryKey = 'id_ovo_response_code';

	protected $fillable = [
		'response_code',
		'response_description',
		'created_at',
		'updated_at'
	];
}
