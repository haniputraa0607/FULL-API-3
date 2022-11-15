<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoPaymentGatewayTransaction extends Model
{
    protected $table = 'promo_payment_gateway_transactions';
    protected $primaryKey = 'id_promo_payment_gateway_transaction';

    protected $fillable = [
        'id_rule_promo_payment_gateway',
        'payment_gateway_user',
        'id_user',
        'id_transaction',
        'amount',
        'total_received_cashback',
        'status_active'
    ];
}
