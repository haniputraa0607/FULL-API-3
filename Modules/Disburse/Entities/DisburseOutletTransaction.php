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
        'income_central_old',
        'income_outlet',
        'income_outlet_old',
        'expense_central',//total expenses for central
        'expense_central_old',
        'fee_item',//charged for outlet
        'discount',//charged for outlet
        'discount_central',
        'payment_charge',//charged for outlet
        'payment_charge_old',
        'point_use_expense',//charged for outlet
        'subscription',//charged for outlet
        'subscription_central',
        'bundling_product_total_discount',
        'bundling_product_fee_outlet',
        'bundling_product_fee_central',
        'fee', //percent fee of fee item,
        'fee_product_plastic_status',
        'mdr_charged',
        'mdr',//percent fee of payment gateway
        'mdr_central',//income fee from payment gateway for central
        'mdr_type',//percent or nominal
        'charged_point_central',
        'charged_point_outlet',
        'charged_promo_central',
        'charged_promo_outlet',
        'charged_subscription_central',
        'charged_subscription_outlet',
        'id_rule_promo_payment_gateway',
        'status_validation_promo_payment_gateway',
        'fee_promo_payment_gateway_type',
        'fee_promo_payment_gateway',
        'fee_promo_payment_gateway_central',
        'fee_promo_payment_gateway_outlet',
        'charged_promo_payment_gateway',
        'charged_promo_payment_gateway_central',
        'charged_promo_payment_gateway_outlet'
    ];
}
