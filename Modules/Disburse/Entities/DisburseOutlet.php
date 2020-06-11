<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class DisburseOutlet extends Model
{
    protected $table = 'disburse_outlet';
	protected $primaryKey = 'id_disburse_outlet';

	protected $fillable = [
	    'id_disburse',
	    'id_outlet',
        'disburse_nominal',
        'total_income_central',
        'total_expense_central'
	];
}
