<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class TimeSchedule extends Model
{
    protected $table = 'time_schedules';

    protected $primaryKey = 'id_time_schedule';

    protected $fillable   = [
        'id_doctor_schedule',
        'start_time',
        'end_time',
        'remaining_quota_session',
        'status_session'
    ];

    public function scopeOnlyAvailabile($query)
    {
        return $query->where('status_session', "available");
    }
}
