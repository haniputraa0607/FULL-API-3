<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDuplicatePayment extends Model
{
	protected $primaryKey = 'id_transaction_duplicate_payment';

	protected $casts = [
		'id_transaction_duplicate' => 'int',
		'payment_amount' => 'int',
	];

	protected $fillable = [
		'id_transaction_duplicate',
		'payment_type',
		'payment_name',
		'payment_amount'
	];
	
	public function transactionDuplicate()
	{
		return $this->belongsTo(\App\Http\Models\TransactionDuplicate::class, 'id_transaction_duplicate');
	}
	
}
