<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class BankAccountOutlet extends Model
{
    protected $table = 'bank_account_outlets';
	protected $primaryKey = 'id_bank_account_outlet';

	protected $fillable = [
	    'id_bank_account',
		'id_outlet'
	];
}
