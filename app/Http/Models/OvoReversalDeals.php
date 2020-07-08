<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class OvoReversalDeals extends Model
{
	protected $primaryKey = 'id_ovo_reversal_deals';

	protected $fillable = [
		'id_deals_user',
		'id_deals_payment_ovo',
		'date_push_to_pay',
		'request',
		'created_at',
		'updated_at'
	];
}
