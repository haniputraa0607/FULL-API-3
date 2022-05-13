<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\DoctorClinic;
use Modules\Doctor\Entities\DoctorSpecialist;
use Modules\Doctor\Entities\DoctorSchedule;

class Doctor extends Model
{
    protected $table = 'doctors';

    protected $primaryKey = 'id_doctor';

    protected $fillable   = [
        'doctor_name',
        'doctor_phone',
        'password',
        'id_doctor_clinic',
        'id_doctor_specialist',
        'doctor_status',
        'doctor_session_price',
        'is_active',
        'doctor_sevice',
        'doctor_photo'
    ];

    public function clinic()
    {
        return $this->belongsTo(DoctorClinic::class, 'id_doctor_clinic', 'id_doctor_clinic');
    }

    public function specialists()
    {
        return $this->belongsToMany(DoctorSpecialist::class, 'doctors_specialists_pivots', 'id_doctor', 'id_doctor_specialist');
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('is_active', true);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'id_doctor', 'id_doctor');
    }
}
