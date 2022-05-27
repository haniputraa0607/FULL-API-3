<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorDevice extends Model
{
    protected $table = 'doctor_devices';

    protected $primaryKey = 'id_doctor_device';

    protected $fillable = [
        'id_doctor',
        'device_type',
        'device_id',
        'device_token'
    ];
}
