<?php

namespace Modules\Merchant\Entities;

use Illuminate\Database\Eloquent\Model;

class MerchantLogBalance extends Model
{
    protected $table = 'merchant_log_balances';
    protected $primaryKey = 'id_merchant_log_balance';

    protected $fillable = [
        'id_merchant',
        'merchant_balance',
        'merchant_balance_before',
        'merchant_balance_after',
        'merchant_balance_id_reference',
        'merchant_balance_source',
        'merchant_balance_status'
    ];
}
