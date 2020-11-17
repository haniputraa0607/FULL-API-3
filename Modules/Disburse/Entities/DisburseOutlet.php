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
        'total_expense_central',
        'total_fee_item',
        'total_omset',
        'total_promo_charged',
        'total_subtotal',
        'total_discount',
        'total_delivery_price',
        'total_payment_charge',
        'total_point_use_expense',
        'total_subscription',
        'total_discount_delivery_charged',
        'total_discount_delivery'
	];
}
