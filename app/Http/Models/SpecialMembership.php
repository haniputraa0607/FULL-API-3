<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialMembership extends Model
{
    protected $primaryKey = 'id_special_membership';

	protected $fillable = [
		'special_membership_name',
		'payment_method',
		'benefit_point_multiplier',
		'benefit_cashback_multiplier',
		'cashback_maximum',
		'created_at',
		'updated_at'
	];
}
