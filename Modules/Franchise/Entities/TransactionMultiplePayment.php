<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionMultiplePayment extends Model
{
    protected $connection = 'mysql3';
    protected $primaryKey = 'id_transaction_multiple_payment';

    protected $fillable = [
        'id_transaction',
        'type',
        'id_payment',
        'created_at',
        'updated_at',
        'payment_detail',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id_transaction');
    }
}
