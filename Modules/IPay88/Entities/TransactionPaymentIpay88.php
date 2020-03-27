<?php

namespace Modules\IPay88\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionPaymentIpay88 extends Model
{
    protected $fillable = [
        'id_transaction',
        'from_user',
        'from_backend',
        'merchant_code',
        'payment_id',
        'ref_no',
        'amount',
        'currency',
        'remark',
        'trans_id',
        'auth_code',
        'status',
        'err_desc',
        'signature',
        'xfield1'
    ];
}
