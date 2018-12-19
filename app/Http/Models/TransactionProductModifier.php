<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionProductModifier extends Model
{
	protected $primaryKey = 'id_transaction_product_modifier';

	protected $casts = [
		'id_transaction_product' => 'int',
		'id_transaction' => 'int',
		'id_product' => 'int',
		'id_product_modifier' => 'int',
		'id_outlet' => 'int',
		'id_user' => 'int',
	];

	protected $fillable = [
		'id_transaction_product',
		'id_transaction',
		'id_product',
		'id_product_modifier',
		'id_outlet',
		'id_user',
		'type',
		'code',
		'text',
		'qty',
		'datetime',
		'trx_type',
		'sales_type',
		'created_at',
		'updated_at'
	];
}
