<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionPaymentManual
 * 
 * @property int $id_transaction_payment_manual
 * @property int $id_transaction
 * @property int $id_manual_payment_method
 * @property \Carbon\Carbon $payment_date
 * @property \Carbon\Carbon $payment_time
 * @property string $payment_bank
 * @property string $payment_method
 * @property string $payment_account_number
 * @property string $payment_account_name
 * @property int $payment_nominal
 * @property string $payment_receipt_image
 * @property string $payment_note
 * @property string $payment_note_confirm
 * @property \Carbon\Carbon $confirmed_at
 * @property \Carbon\Carbon $cancelled_at
 * @property int $id_user_confirming
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Transaction $transaction
 * @property \App\Http\Models\ManualPaymentMethod $manual_payment_method
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class TransactionPaymentManual extends Model
{
	protected $primaryKey = 'id_transaction_payment_manual';

	protected $casts = [
		'id_transaction' => 'int',
		'id_manual_payment_method' => 'int',
		'payment_nominal' => 'int',
		'id_user_confirming' => 'int'
	];

	protected $dates = [
		'payment_date' => 'datetime:Y-m-d',
		'payment_time' => 'datetime:H:i:s',
		'confirmed_at' => 'datetime:Y-m-d H:i:s',
		'cancelled_at' => 'datetime:Y-m-d H:i:s',
	];

	protected $fillable = [
		'id_transaction',
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
