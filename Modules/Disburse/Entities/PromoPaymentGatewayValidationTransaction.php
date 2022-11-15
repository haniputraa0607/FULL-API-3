<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoPaymentGatewayValidationTransaction extends Model
{
    protected $table = 'promo_payment_gateway_validation_transactions';
    protected $primaryKey = 'id_promo_payment_gateway_validation_transaction';

    protected $fillable = [
        'id_promo_payment_gateway_validation',
        'id_transaction',
        'reference_id',
        'validation_status',
        'new_cashback',
        'old_cashback',
        'notes'
    ];
}
