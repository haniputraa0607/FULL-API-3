<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:17 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ManualPaymentTutorial
 * 
 * @property int $id_manual_payment_tutorial
 * @property int $id_manual_payment_method
 * @property string $payment_tutorial
 * @property int $payment_tutorial_no
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\ManualPaymentMethod $manual_payment_method
 *
 * @package App\Models
 */
class ManualPaymentTutorial extends Model
{
	protected $primaryKey = 'id_manual_payment_tutorial';

	protected $casts = [
		'id_manual_payment_method' => 'int',
		'payment_tutorial_no' => 'int'
	];

	protected $fillable = [
		'id_manual_payment_method',
		'payment_tutorial',
		'payment_tutorial_no'
	];

	public function manual_payment_method()
	{
		return $this->belongsTo(\App\Http\Models\ManualPaymentMethod::class, 'id_manual_payment_method');
	}
}
