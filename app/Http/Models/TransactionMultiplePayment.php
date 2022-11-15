<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionMultiplePayment extends Model
{
    protected $primaryKey = 'id_transaction_multiple_payment';

    protected $fillable = [
        'id_transaction',
        'id_transaction_group',
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
