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
use App\Traits\HasInfobibToken;

class Doctor extends Authenticatable 
{
    protected $table = 'doctors';

    use HasApiTokens, Notifiable, HasInfobibToken;

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

    public function getAuthPassword() {
		return $this->password;
	}


    protected $fillable   = [
        'doctor_name',
        'doctor_phone',
        'provider',
        'phone_verified',
        'password',
        'birthday',
        'gender',
        'celebrate',
        'address',
        'alumni',
        'practice_experience',
        'practice_experience_place',
        'practice_lisence_number',
        'registration_certificate_number',
        'id_card_number',
        'id_outlet',
        'doctor_status',
        'doctor_session_price',
        'is_active',
        'doctor_service',
        'doctor_photo',
        'total_rating',
        'sms_increment',
        'schedule_toogle',
        'notification_toogle',
        'count_login_failed',
        'pin_changed',
        'otp_forgot',
        'otp_valid_time',
        'id_agent',
        'id_queue'
    ];

    protected $appends = [
        'url_doctor_photo',
        'challenge_key2'
    ];

    public function clinic()
    {
        return $this->belongsTo(DoctorClinic::class, 'id_doctor_clinic', 'id_doctor_clinic');
    }

    public function outlet(){
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet', 'id_outlet');
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

    public function getUrlDoctorPhotoAttribute()
    {
        if(!empty($this->attributes['doctor_photo'])){
            $url_doctor_photo = env('STORAGE_URL_API').$this->attributes['doctor_photo'];
        } else {
            $url_doctor_photo = null;
        }

        return $url_doctor_photo;
    }

    public function getChallengeKey2Attribute()
    {
    	$password = md5($this->password);
    	return $password.'15F1AB77951B5JAO';
    }

    public function getNameAttribute()
    {
        return $this->doctor_name;
    }
}
