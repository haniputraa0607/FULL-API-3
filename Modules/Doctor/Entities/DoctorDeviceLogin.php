<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorDeviceLogin extends Model
{
    protected $table = 'doctor_device_login';

    protected $primaryKey = 'id_doctor_last_login';

    protected $fillable = [
        'id_doctor',
        'device_id',
        'last_login',
        'status'
    ];
}
