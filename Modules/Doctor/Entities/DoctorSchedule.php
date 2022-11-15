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
        'day',
        'order',
        'is_active'
    ];

    protected $appends = [
        'day_formatted'
    ];

    public function schedule_time()
    {
        return $this->hasMany(TimeSchedule::class, 'id_doctor_schedule', 'id_doctor_schedule');
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDayFormattedAttribute()
    {
        $day = null;
        switch (strtolower($this->attributes['day'])) {
            case 'monday':
                $day = "Senin";
                break;
            case 'tuesday':
                $day = "Selasa";
                break;
            case 'wednesday':
                $day = "Rabu";
                break;
            case 'thursday':
                $day = "Kamis";
                break;
            case 'friday':
                $day = "Jumat";
                break;
            case 'saturday':
                $day = "Sabtu";
                break;
            case 'sunday':
                $day = "Minggu";
                break;
            default:
                $day = "none";
                break;
        }

        return $day;
    }
}
