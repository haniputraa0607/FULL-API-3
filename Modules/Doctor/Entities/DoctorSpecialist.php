<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\DoctorSpecialistCategory;
use Modules\Doctor\Entities\Doctor;

class DoctorSpecialist extends Model
{
    protected $table = 'doctors_specialists';

    protected $primaryKey = 'id_doctor_specialist';

    protected $fillable   = [
        'doctor_specialist_name',
        'id_doctor_specialist_category'
    ];

    public function category()
    {
        return $this->belongsTo(DoctorSpecialistCategory::class, 'id_doctor_specialist_category', 'id_doctor_specialist_category');
    }

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctors_specialists_pivots', 'id_doctor', 'id_doctor_specialist');
    }
}
