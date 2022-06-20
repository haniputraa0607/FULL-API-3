<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistAttendanceRequest extends Model
{
    protected $primaryKey = 'id_hairstylist_attendance_request';
    protected $fillable = [
        'id_user_hair_stylist',
        'id_hairstylist_schedule_date',
        'clock_in',
        'clock_out',
        'notes',
        'status',
        'id_outlet',
    ];
}
