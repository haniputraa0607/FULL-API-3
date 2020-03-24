<?php

namespace Modules\OutletApp\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletAppOtp extends Model
{
    protected $fillable = [
    	'feature',
    	'pin',
    	'id_user_outlet',
    	'id_outlet'
    ];
}
