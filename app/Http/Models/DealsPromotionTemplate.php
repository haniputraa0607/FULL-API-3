<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DealsPromotionTemplate extends Model
{
	protected $primaryKey = 'id_deals_promotion_template';

	protected $dates = [
		'deals_start',
		'deals_end'
	];

	protected $fillable = [
		'deals_type',
		'deals_voucher_type',
		'deals_promo_id_type',
		'deals_promo_id',
		'deals_nominal',
		'deals_voucher_value',
		'deals_voucher_given',
		'deals_title',
		'deals_description',
		'deals_short_description',
		'deals_image',
		'deals_start',
		'deals_end',
		'deals_voucher_duration',
		'deals_voucher_expired',
		'deals_total_voucher',
		'deals_list_voucher',
		'deals_list_outlet',
	];

	protected $appends  = ['url_deals_image'];

	// ATTRIBUTE IMAGE URL
	public function getUrlDealsImageAttribute() {
		if (empty($this->deals_image)) {
            return env('APP_API_URL').'img/default.jpg';
        }
        else {
            return env('APP_API_URL').$this->deals_image;
        }
	}

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'deals_outlets', 'id_deals', 'id_outlet');
	}

	public function deals_vouchers()
	{
		return $this->hasMany(\App\Http\Models\DealsVoucher::class, 'id_deals');
	}

	public function deals_subscriptions()
	{
		return $this->hasMany(DealsSubscription::class, 'id_deals');
	}
}
