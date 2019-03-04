<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UsersMembershipPromoId extends Model
{
	protected $primaryKey = 'id_user_membership_promo_id';

	protected $fillable = [
		'id_users_membership',
		'promo_name',
		'promo_id',
	];

	public function membership()
	{
		return $this->belongsTo(\App\Http\Models\Membership::class, 'id_membership');
	}
}
