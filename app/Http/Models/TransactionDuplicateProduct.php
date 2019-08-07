<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDuplicateProduct extends Model
{
	protected $primaryKey = 'id_transaction_duplicate_product';

	protected $casts = [
		'id_transaction_duplicate' => 'int',
		'id_product' => 'int',
		'transaction_product_qty' => 'int',
	];

	protected $fillable = [
		'id_transaction_duplicate',
		'id_product',
		'transaction_product_code',
		'transaction_product_name',
		'transaction_product_qty',
		'transaction_product_price',
		'transaction_product_subtotal',
		'transaction_product_note'
	];
	
	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

	public function transactionDuplicate()
	{
		return $this->belongsTo(\App\Http\Models\TransactionDuplicate::class, 'id_transaction_duplicate');
	}
	
}
