<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class BankName extends Model
{
    protected $table = 'bank_name';
	protected $primaryKey = 'id_bank_name';

	protected $fillable = [
	    'bank_code',
		'bank_name'
	];
}
