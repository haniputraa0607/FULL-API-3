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
        'expense_central',//total expenses for central
        'fee_item',//charged for outlet
        'discount',//charged for outlet
        'discount_central',
        'payment_charge',//charged for outlet
        'point_use_expense',//charged for outlet
        'subscription',//charged for outlet
        'subscription_central',
        'fee', //percent fee of fee item
        'mdr_charged',
        'mdr',//percent fee of payment gateway
        'mdr_central',//income fee from payment gateway for central
        'mdr_type',//percent or nominal
        'charged_point_central',
        'charged_point_outlet',
        'charged_promo_central',
        'charged_promo_outlet',
        'charged_subscription_central',
        'charged_subscription_outlet'
	];
}
