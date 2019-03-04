<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPromoId extends Model
{
	protected $primaryKey = 'id_membership_promo_id';

	protected $fillable = [
		'id_membership',
		'promo_name',
		'promo_id',
	];

	public function membership()
	{
		return $this->belongsTo(\App\Http\Models\Membership::class, 'id_membership');
	}
}
