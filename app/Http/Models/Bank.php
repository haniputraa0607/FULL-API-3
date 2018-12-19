<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
	protected $table = 'banks';

	protected $primaryKey = 'id_bank';
	
	protected $fillable   = [
		'nama_bank',
		'created_at',
		'updated_at'
	];
}
