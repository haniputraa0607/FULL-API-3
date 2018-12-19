<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogTopupManual extends Model
{
    protected $primaryKey = 'id_log_topup_manual';

	protected $casts = [
		'id_transaction'           => 'int',
		'id_manual_payment_method' => 'int',
		'payment_nominal'          => 'int',
		'id_user_confirming'       => 'int'
	];

	protected $dates = [
		'payment_date' => 'datetime:Y-m-d',
		'payment_time' => 'datetime:H:i:s',
		'confirmed_at' => 'datetime:Y-m-d H:i:s',
		'cancelled_at' => 'datetime:Y-m-d H:i:s',
	];

	protected $fillable = [
		'id_transaction',
		'id_log_topup',
		'id_manual_payment_method',
		'payment_date',
		'payment_time',
		'payment_bank',
		'payment_method',
		'payment_account_number',
		'payment_account_name',
		'payment_nominal',
		'payment_receipt_image',
		'payment_note',
		'payment_note_confirm',
		'confirmed_at',
		'cancelled_at',
		'id_user_confirming'
	];

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

	public function manual_payment_method()
	{
		return $this->belongsTo(\App\Http\Models\ManualPaymentMethod::class, 'id_manual_payment_method');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user_confirming');
	}
}
