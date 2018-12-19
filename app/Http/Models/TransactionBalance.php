<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBalance extends Model
{
	protected $primaryKey = 'id_transaction_balance';

    protected $fillable = [
		'receipt_number',
		'id_user',
		'id_outlet',
		'id_transaction',
		'nominal',
		'approval_code',
		'expired_at',
		'status',
		'created_at',
		'updated_at',
	];
}
