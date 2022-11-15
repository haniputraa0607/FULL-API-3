<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionPromo extends Model
{
    protected $table = 'transaction_promos';

    protected $primaryKey = 'id_transaction_promo';

    protected $casts = [
        'id_transaction' => 'int',
        'id_deals_user' => 'int',
        'id_promo_campaign_promo_code' => 'int',
        'discount_value' => 'int'
    ];

    protected $fillable = [
        'id_transaction',
        'promo_name',
        'promo_type',
        'id_deals_user',
        'id_promo_campaign_promo_code',
        'discount_value'
    ];
}
