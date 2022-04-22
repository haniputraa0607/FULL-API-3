<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorService extends Model
{
    protected $table = 'doctors_services';

    protected $primaryKey = 'id_doctor_service';

    protected $fillable   = [
        'doctor_service_name'
    ];
}
