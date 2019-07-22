<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPaymentOvo extends Model
{
	protected $primaryKey = 'id_transaction_payment_ovo';

	protected $casts = [
		'id_transaction' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'is_production',
		'push_to_pay_at',
		'reversal',
		'amount',
		'trace_number',
		'approval_code',
		'response_code',
		'response_detail',
		'response_description',
		'merchant_invoice',
		'batch_no',
		'reference_number',
		'phone',
		'ovoid',
		'cash_used',
		'ovo_points_earned',
		'cash_balance',
		'full_name',
		'ovo_points_used',
		'ovo_points_balance',
		'payment_type'
	];

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}
}
