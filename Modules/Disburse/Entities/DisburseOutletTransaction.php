<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class DisburseOutletTransaction extends Model
{
    protected $table = 'disburse_outlet_transactions';
	protected $primaryKey = 'id_disburse_transaction';

	protected $fillable = [
	    'id_disburse_outlet',
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
