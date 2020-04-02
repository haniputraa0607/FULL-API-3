<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class Disburse extends Model
{
    protected $table = 'disburse';
	protected $primaryKey = 'id_disburse';

	protected $fillable = [
	    'disburse_nominal',
		'disburse_status',
        'beneficiary_bank_name',
        'beneficiary_account_number',
        'recipient_name',
        'request',
        'response'
	];
}
