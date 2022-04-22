<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorSpecialistCategory extends Model
{
    protected $table = 'doctors_specialists_categories';

    protected $primaryKey = 'id_doctor_specialist_category';

    protected $fillable   = [
        'doctor_specialist_category_name'
    ];
}
