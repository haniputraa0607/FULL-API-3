<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\Doctor;

class DoctorUpdateData extends Model
{
    protected $table = 'doctor_update_datas';
    protected $primaryKey = 'id_doctor_update_data';

    protected $casts = [
        'id_doctor' => 'int',
        'approve_by' => 'int'
    ];

    protected $dates = [
        'approve_at',
        'reject_at'
    ];

    protected $fillable = [
        'id_doctor_update_data',
        'id_doctor',
        'approve_by',
        'field',
        'new_value',
        'notes',
        'approve_at',
        'reject_at'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }
}
