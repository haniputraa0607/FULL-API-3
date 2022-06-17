<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\DoctorClinic;
use Modules\Doctor\Entities\DoctorSpecialist;
use App\Lib\MyHelper;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Modules\Doctor\Entities\DoctorSchedule;

class Doctor extends Authenticatable 
{
    protected $table = 'doctors';

    use HasApiTokens, Notifiable;

    protected $primaryKey = 'id_doctor';

    public function findForPassport($username) {
		if(substr($username, 0, 2) == '62'){
			$username = substr($username,2);
		}elseif(substr($username, 0, 3) == '+62'){
			$username = substr($username,3);
		}

		if(substr($username, 0, 1) != '0'){
			$username = '0'.$username;
		}

        return $this->where('doctor_phone', $username)->first();
    }


    protected $fillable   = [
        'doctor_name',
        'doctor_phone',
        'phone_verified',
        'password',
        'id_doctor_clinic',
        'id_doctor_specialist',
        'doctor_status',
        'doctor_session_price',
        'is_active',
        'doctor_sevice',
        'doctor_photo',
        'total_rating',
        'sms_increment',
        'schedule_toogle',
        'notification_toogle'
    ];

    public function clinic()
    {
        return $this->belongsTo(DoctorClinic::class, 'id_doctor_clinic', 'id_doctor_clinic');
    }

    public function specialists()
    {
        return $this->belongsToMany(DoctorSpecialist::class, 'doctors_specialists_pivots', 'id_doctor', 'id_doctor_specialist');
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnlyVerified($query)
    {
        return $query->where('phone_verified', true);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'id_doctor', 'id_doctor');
    }
}
