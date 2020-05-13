<?php

namespace Modules\IPay88\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentIpay88 extends Model
{
    public $primaryKey  = 'id_subscription_payment_ipay88';
    protected $fillable = [
        'id_subscription',
        'id_subscription_user',
        'order_id',
        'from_user',
        'from_backend',
        'merchant_code',
        'payment_id',
        'payment_method',
        'ref_no',
        'amount',
        'currency',
        'remark',
        'trans_id',
        'auth_code',
        'status',
        'err_desc',
        'signature',
        'xfield1',
        'requery_response',
        'user_contact',
    ];
}
