<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UserTrxProduct extends Model
{
    protected $primaryKey = 'id_user_trx_product';

	protected $casts = [
		'id_user_trx_product' => 'int',
		'id_user' => 'int',
        'id_product' => 'int',
        'product_qty'
	];

	protected $dates = [
		'last_trx_date'
	];

	protected $fillable = [
		'id_user_trx_product',
        'id_user',
        'id_product',
        'last_trx_date',
        'product_qty'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}
}
