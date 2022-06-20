<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistAttendanceLog extends Model
{
    protected $primaryKey = 'id_hairstylist_attendance_log';
    protected $fillable = [
        'id_hairstylist_attendance',
        'type',
        'datetime',
        'latitude',
        'longitude',
        'location_name',
        'photo_path',
        'status',
        'approved_by',
        'notes',
    ];

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? config('url.storage_url_api') . $this->photo_path : null;
    }

    public function hairstylist_attendance()
    {
        return $this->belongsTo(HairstylistAttendance::class, 'id_hairstylist_attendance');
    }
}
