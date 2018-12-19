<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DealsSubscription extends Model
{
	protected $primaryKey = 'id_deals_subscription';

	protected $fillable = [
		'id_deals',
		'promo_type',
		'promo_value',
		'total_voucher',
		'voucher_start',
		'voucher_end',
	];

	public function deals()
	{
		return $this->hasOne(\App\Http\Models\Deal::class, 'id_deals', 'id_deals');
	}

	public function deals_vouchers()
	{
		return $this->hasMany(\App\Http\Models\DealsVoucher::class, 'id_deals_subscription', 'id_deals_subscription');
	}

}
