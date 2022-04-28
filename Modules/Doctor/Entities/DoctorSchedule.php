<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\TimeSchedule;

class DoctorSchedule extends Model
{
    protected $table = 'doctor_schedules';

    protected $primaryKey = 'id_doctor_schedule';

    protected $fillable   = [
        'id_doctor',
        'date',
        'is_active'
    ];

    public function schedule_time()
    {
        return $this->hasMany(TimeSchedule::class, 'id_doctor_schedule', 'id_doctor_schedule');
    }
}
