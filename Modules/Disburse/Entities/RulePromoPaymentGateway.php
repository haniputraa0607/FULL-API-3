<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class RulePromoPaymentGateway extends Model
{
    protected $table = 'rule_promo_payment_gateway';
	protected $primaryKey = 'id_rule_promo_payment_gateway';

	protected $fillable = [
	    'promo_payment_gateway_code',
	    'name',
	    'payment_gateway',
		'start_date',
        'end_date',
        'limit_promo_total',
        'limit_promo_additional',
        'limit_promo_additional_type',
        'limit_promo_additional_account_type',
        'cashback_type',
        'cashback',
        'maximum_cashback',
        'minimum_transaction',
        'charged_type',
        'charged_payment_gateway',
        'charged_jiwa_group',
        'charged_central',
        'charged_outlet',
        'mdr_setting',
        'start_status',
        'last_updated_by'
	];
}
