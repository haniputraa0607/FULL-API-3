<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorAppVersion extends Model
{
    protected $table = 'doctor_app_versions';
    protected $primaryKey = 'id_doctor_app_version';
    protected $fillable = [
        'app_type',
        'app_version',
        'rules',
    ];
}
