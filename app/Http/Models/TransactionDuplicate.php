<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDuplicate extends Model
{
	protected $primaryKey = 'id_transaction_duplicate';

	protected $casts = [
		'id_user' => 'int',
		'id_transaction' => 'int',
		'id_outlet_1' => 'int',
		'id_outlet_2' => 'int',
	];

	protected $dates = [
		'transaction_date',
		'sync_datetime_1',
		'sync_datetime_2'
	];

	protected $fillable = [
		'id_user',
		'id_transaction',
		'id_outlet',
		'id_outlet_duplicate',
		'transaction_receipt_number',
		'outlet_code',
		'outlet_code_duplicate',
		'outlet_name',
		'outlet_name_duplicate',
		'user_name',
		'user_phone',
		'transaction_cashier',
		'transaction_date',
		'transaction_subtotal',
		'transaction_service',
		'transaction_tax',
		'transaction_grandtotal',
		'sync_datetime',
		'sync_datetime_duplicate'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

	public function products()
	{
		return $this->hasMany(\App\Http\Models\TransactionDuplicateProduct::class, 'id_transaction_duplicate');
	}

	public function payments()
	{
		return $this->hasMany(\App\Http\Models\TransactionDuplicatePayment::class, 'id_transaction_duplicate');
	}
	
}
