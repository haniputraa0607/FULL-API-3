<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionProductConsultationRedeem extends Model
{
    protected $table = 'transaction_product_consultation_redeem';

    protected $primaryKey = 'id_transaction_product_consultation_redeem';

    protected $fillable = [
        'id_transaction_product',
        'id_transaction_consultation_recomendation',
        'qty'
    ];
}
