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
        'status_session'
    ];

    protected $appends = [
        'start_time',
        'end_time'
    ];

    public function getStartTimeAttribute()
    {
        return date('H:i', strtotime($this->attributes['start_time']));
    }

    public function getEndTimeAttribute()
    {
        return date('H:i', strtotime($this->attributes['end_time']));
    }
}
