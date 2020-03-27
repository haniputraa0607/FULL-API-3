<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletScheduleUpdate extends Model
{
    protected $fillable = [
    	'id_outlet',
    	'id_outlet_schedule',
    	'id_user',
    	'id_outlet_app_otp',
    	'user_type',
    	'date_time',
    	'old_data',
    	'new_data'
    ];
}
