<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionPaymentOffline
 * 
 * @property int $id_transaction_payment_offline
 * @property int $id_transaction
 * @property string $payment_type
 * @property string $payment_bank
 * @property int $payment_amount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionPaymentOffline extends Model
{
	protected $primaryKey = 'id_transaction_payment_offline';

	protected $casts = [
		'id_transaction' => 'int',
		'id_payment_method' => 'int',
		'payment_amount' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'id_payment_method',
		'payment_type',
		'payment_bank',
		'payment_amount'
	];

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}
	
	public function payment_method(){
        return $this->belongsTo(\App\Http\Models\PaymentMethod::class, 'id_payment_method');
    }

}
