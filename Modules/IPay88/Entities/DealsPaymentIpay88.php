<?php

namespace Modules\IPay88\Entities;

use Illuminate\Database\Eloquent\Model;

class DealsPaymentIpay88 extends Model
{
    public $primaryKey = 'id_deals_payment_ipay88';
    protected $fillable = [
        'id_deals',
        'id_deals_user',
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
        'requery_response'
    ];
}
