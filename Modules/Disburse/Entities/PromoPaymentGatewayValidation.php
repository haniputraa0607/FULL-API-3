<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoPaymentGatewayValidation extends Model
{
    protected $table = 'promo_payment_gateway_validation';
    protected $primaryKey = 'id_promo_payment_gateway_validation';

    protected $fillable = [
        'id_user',
        'id_rule_promo_payment_gateway',
        'processing_status',
        'reference_by',
        'validation_cashback_type',
        'validation_payment_type',
        'override_mdr_status',
        'override_mdr_percent_type',
        'start_date_periode',
        'end_date_periode',
        'correct_get_promo',
        'not_get_promo',
        'must_get_promo',
        'wrong_cashback',
        'invalid_data',
        'file'
    ];
}
