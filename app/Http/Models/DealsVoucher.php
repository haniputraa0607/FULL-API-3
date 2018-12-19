<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DealsVoucher
 * 
 * @property int $id_deals_voucher
 * @property int $id_deals
 * @property string $voucher_code
 * @property string $deals_voucher_status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Deal $deal
 *
 * @package App\Models
 */
class DealsVoucher extends Model
{
	protected $primaryKey = 'id_deals_voucher';

	protected $casts = [
		'id_deals' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'id_deals_subscription',
		'voucher_code',
		'deals_voucher_status',
		'created_at',
		'updated_at'
	];

	public function deal()
	{
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}

	public function deals_user() {
		return $this->hasMany(\App\Http\Models\DealsUser::class, 'id_deals_voucher', 'id_deals_voucher');
	}

	public function deals_voucher_user() {
		return $this->belongsToMany(\App\Http\Models\User::class, 'deals_users', 'id_deals_voucher', 'id_user');
	}

}
