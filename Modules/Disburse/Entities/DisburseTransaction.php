<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class DisburseTransaction extends Model
{
    protected $table = 'disburse_transactions';
	protected $primaryKey = 'id_disburse_transaction';

	protected $fillable = [
	    'id_disburse',
        'id_transaction',
        'income_central',
        'income_outlet',
        'expense_central',
        'fee',
        'mdr_charged',
        'mdr',
        'mdr_central',
        'mdr_type',
        'charged_point_central',
        'charged_point_outlet',
        'charged_promo_central',
        'charged_promo_outlet'
	];
}
