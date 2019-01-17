<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPaymentBalance extends Model
{
    protected $primaryKey = 'id_transaction_payment_balance';

	protected $fillable = [
		'id_transaction',
		'balance_nominal',
		'created_at',
		'updated_at'
	];

	public function transaction()
	{
		return $this->belongsTo(Transaction::class, 'id_transaction', 'id_transaction');
	}
}
