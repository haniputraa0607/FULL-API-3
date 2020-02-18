<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DealsPaymentOvo extends Model
{
	protected $primaryKey = 'id_deals_payment_ovo';

	protected $fillable = [
		'id_deals',
		'id_deals_user',
		'is_production',
		'push_to_pay_at',
		'reversal',
		'amount',
		'order_id',
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
}
