<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class RulePromoPaymentGatewayBrand extends Model
{
    protected $table = 'rule_promo_payment_gateway_brand';
    protected $primaryKey = 'id_rule_promo_payment_gateway_brand';

    protected $fillable = [
        'id_rule_promo_payment_gateway',
        'id_brand'
    ];
}
