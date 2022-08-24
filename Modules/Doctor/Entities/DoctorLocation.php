<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorLocation extends Model
{
    protected $table = 'doctor_login';

    protected $primaryKey = 'id_doctor_last_login';

    protected $fillable = [
        'id_doctor',
        'action',
        'lat',
        'lng'
    ];
}
