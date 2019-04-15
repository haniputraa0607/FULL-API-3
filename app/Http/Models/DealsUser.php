<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DealsUser
 * 
 * @property int $id_deals_user
 * @property int $id_deals_voucher
 * @property int $id_user
 * @property int $id_outlet
 * @property string $voucher_hash
 * @property \Carbon\Carbon $claimed_at
 * @property \Carbon\Carbon $redeemed_at
 * @property \Carbon\Carbon $used_at
 * @property int $voucher_price_point
 * @property int $voucher_price_cash
 * @property string $paid_status
 * @property \Carbon\Carbon $voucher_expired_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class DealsUser extends Model
{
	protected $primaryKey = 'id_deals_user';

	protected $casts = [
		'id_deals_voucher' => 'int',
		'id_user' => 'int',
		'id_outlet' => 'int',
		'voucher_price_point' => 'int',
		'voucher_price_cash' => 'int'
	];

	protected $dates = [
		'claimed_at' => 'datetime:Y-m-d H:i:s',
		'redeemed_at',
		'used_at',
		'voucher_active_at',
		'voucher_expired_at'
	];

	protected $fillable = [
		'id_deals_voucher',
		'id_deals',
		'id_user',
		'id_outlet',
		'voucher_hash',
		'voucher_hash_code',
		'claimed_at',
		'redeemed_at',
		'used_at',
		'voucher_price_point',
		'payment_method',
		'balance_nominal',
		'voucher_price_cash',
		'paid_status',
		'voucher_active_at',
		'voucher_expired_at'
	];

	function user() {
		return $this->belongsTo(User::class, 'id_user', 'id')->select('id', 'name', 'phone', 'email', 'gender', 'phone_verified', 'email_verified', 'level', 'birthday', 'points');
	}

	function userMid()
	{
		return $this->belongsTo(User::class, 'id_user', 'id')->select('id', 'name', 'phone', 'email');
	}

	function outlet() {
		return $this->belongsTo(Outlet::class, 'id_outlet', 'id_outlet')->select('id_outlet','outlet_code', 'outlet_name', 'outlet_address');
	}

	function dealVoucher() {
		return $this->belongsTo(DealsVoucher::class, 'id_deals_voucher', 'id_deals_voucher');
	}

	function deals() {
		return $this->belongsTo(Deal::class, 'id_deals', 'id_deals');
	}
	
	function deals_voucher() {
		return $this->belongsTo(DealsVoucher::class, 'id_deals_voucher', 'id_deals_voucher')->select('id_deals_voucher', 'id_deals', 'voucher_code');
	}
}
