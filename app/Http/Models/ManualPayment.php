<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:17 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ManualPayment
 * 
 * @property int $id_manual_payment
 * @property string $is_virtual_account
 * @property string $manual_payment_name
 * @property string $manual_payment_logo
 * @property string $account_number
 * @property string $account_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection $manual_payment_methods
 *
 * @package App\Models
 */
class ManualPayment extends Model
{
	protected $primaryKey = 'id_manual_payment';

	protected $appends  = ['manual_payment_logo_url'];

	protected $fillable = [
		'is_virtual_account',
		'manual_payment_name',
		'manual_payment_logo',
		'account_number',
		'account_name'
	];

	public function manual_payment_methods()
	{
		return $this->hasMany(\App\Http\Models\ManualPaymentMethod::class, 'id_manual_payment');
	}

	public function getManualPaymentLogoUrlAttribute() {
		if (empty($this->manual_payment_logo)) {
            return env('S3_URL_API').'img/logo.jpg';
        }
        else {
            return env('S3_URL_API').$this->manual_payment_logo;
        }
	}
}
