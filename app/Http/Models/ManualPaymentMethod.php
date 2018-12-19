<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ManualPaymentMethod
 * 
 * @property int $id_manual_payment_method
 * @property int $id_manual_payment
 * @property string $payment_method_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\ManualPayment $manual_payment
 * @property \Illuminate\Database\Eloquent\Collection $deals_payment_manuals
 * @property \Illuminate\Database\Eloquent\Collection $manual_payment_tutorials
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_manuals
 *
 * @package App\Models
 */
class ManualPaymentMethod extends Model
{
	protected $primaryKey = 'id_manual_payment_method';

	protected $casts = [
		'id_manual_payment' => 'int'
	];

	protected $fillable = [
		'id_manual_payment',
		'payment_method_name'
	];

	public function manual_payment()
	{
		return $this->belongsTo(\App\Http\Models\ManualPayment::class, 'id_manual_payment');
	}

	public function deals_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\DealsPaymentManual::class, 'id_manual_payment_method');
	}

	public function manual_payment_tutorials()
	{
		return $this->hasMany(\App\Http\Models\ManualPaymentTutorial::class, 'id_manual_payment_method');
	}

	public function transaction_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentManual::class, 'id_manual_payment_method');
	}
}
