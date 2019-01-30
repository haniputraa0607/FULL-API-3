<?php
namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OutletToken extends Model
{
	protected $primaryKey = 'id_outlet_token';
	protected $fillable = [
		'id_outlet',
		'token'
	];
}
