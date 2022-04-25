<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorClinic extends Model
{
    protected $table = 'doctors_clinics';

    protected $primaryKey = 'id_doctor_clinic';

    protected $fillable   = [
        'doctor_clinic_name',
        'is_active',
    ];
}
