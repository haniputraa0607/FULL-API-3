<?php

namespace Modules\OutletApp\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletAppOtp extends Model
{
	public $primaryKey = 'id_outlet_app_otp';
    protected $fillable = [
    	'feature',
    	'used',
    	'pin',
    	'id_user_outlet',
    	'id_outlet'
    ];
}
