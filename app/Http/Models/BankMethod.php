<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class BankMethod extends Model
{
	protected $table = 'bank_methods';

	protected $primaryKey = 'id_bank_method';
	
	protected $fillable   = [
		'method',
		'created_at',
		'updated_at'
	];
}
