<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OvoReversal extends Model
{
	protected $primaryKey = 'id_ovo_reversal';

	protected $fillable = [
		'id_transaction',
		'id_transaction_payment_ovo',
		'date_push_to_pay',
		'request',
		'created_at',
		'updated_at'
	];

}
