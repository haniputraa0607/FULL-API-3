<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;
use App\Lib\MyHelper;
use Modules\Disburse\Entities\BankAccount;

class UserHairStylist extends Authenticatable
{
	use Notifiable, HasMultiAuthApiTokens;

	public function findForPassport($username) {
        if(substr($username, 0, 2) == '62'){
            $username = substr($username,2);
        }elseif(substr($username, 0, 3) == '+62'){
            $username = substr($username,3);
        }

        if(substr($username, 0, 1) != '0'){
            $username = '0'.$username;
        }

        return $this->where('phone_number', $username)->first();
	}

	public function getAuthPassword() {
		return $this->password;
	}

    protected $table = 'user_hair_stylist';
	protected $primaryKey = 'id_user_hair_stylist';

	protected $hidden = [
		'password'
	];

	protected $fillable = [
	    'id_outlet',
		'id_bank_account',
        'id_hairstylist_category',
        'user_hair_stylist_status',
        'user_hair_stylist_code',
        'user_hair_stylist_score',
        'user_hair_stylist_passed_status',
        'nickname',
        'email',
        'phone_number',
        'fullname',
        'id_card_number',
        'password',
        'level',
        'gender',
        'nationality',
        'birthplace',
        'birthdate',
        'religion',
        'height',
        'weight',
        'recent_job',
        'recent_company',
        'blood_type',
        'recent_address',
        'postal_code',
        'marital_status',
        'email_verified',
        'first_update_password',
        'join_date',
        'approve_by',
        'user_hair_stylist_photo',
        'total_rating',
        'total_balance',
        'latitude',
        'longitude',
        'home_service_status',
        'balance',
        'id_hairstylist_groups',
        'otp_forgot',
        'otp_request_status',
        'otp_valid_time',
        'otp_available_time_request',
        'otp_increment',
        'file_contract'
	];

    public function getChallengeKeyAttribute()
    {
        $password = md5($this->password);
        return $password.'15F1AB77951B5JAO';
    }

    public function bank_account()
    {
        return $this->belongsTo(BankAccount::class, 'id_bank_account');
    }

    public function getUserHairStylistPhotoAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value.'?'.time();
    }

	public function hairstylist_schedules()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistSchedule::class, 'id_user_hair_stylist');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

    public function getPhoneAttribute()
    {
        return $this->phone_number;
    }

    public function documents()
    {
        return $this->hasMany(\Modules\Recruitment\Entities\UserHairStylistDocuments::class, 'id_user_hair_stylist');
    }

    public function experiences()
    {
        return $this->hasOne(\Modules\Recruitment\Entities\UserHairStylistExperience::class, 'id_user_hair_stylist');
    }

    public function location()
    {
        return $this->hasOne(HairstylistLocation::class, 'id_user_hair_stylist');
    }

    public function attendances()
    {
        return $this->hasMany(\Modules\Recruitment\Entities\HairstylistAttendance::class, 'id_user_hair_stylist');
    }

    public function getAttendanceByDate($schedule)
    {
        if (is_string($schedule)) {
            $schedule = $this->hairstylist_schedules()
                ->selectRaw('id_hairstylist_attendance, date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement')
                ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')
                ->where([
                    'schedule_month' => date('m', strtotime($schedule)),
                    'schedule_year' => date('Y', strtotime($schedule))
                ])
                ->whereDate('date', $schedule)
                ->first();
            if (!$schedule || !$schedule->date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
        }
        $attendance = $this->attendances()->where('attendance_date', $schedule->date)->first();
        if (!$attendance) {
            $id_hairstylist_schedule_date = $this->hairstylist_schedules()
                    ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')
                    ->where([
                        'schedule_month' => date('m', strtotime($schedule->date)),
                        'schedule_year' => date('Y', strtotime($schedule->date))
                    ])
                    ->whereDate('date', $schedule->date)
                    ->orderBy('is_overtime')
                    ->first()
                    ->id_hairstylist_schedule_date;
            if (!$id_hairstylist_schedule_date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
            $attendance = $this->attendances()->create([
                'id_hairstylist_schedule_date' => $id_hairstylist_schedule_date,
                'id_outlet' => $this->id_outlet,
                'attendance_date' => $schedule->date,
                'id_user_hair_stylist' => $this->id_user_hair_stylist,
                'clock_in_requirement' => $schedule->clock_in_requirement,
                'clock_out_requirement' => $schedule->clock_out_requirement,
                'clock_in_tolerance' => MyHelper::setting('clock_in_tolerance', 'value', 15),
                'clock_out_tolerance' => MyHelper::setting('clock_out_tolerance', 'value', 0),
            ]);
        }
        return $attendance;
    }

    public function devices()
    {
        return $this->hasMany(UserHairStylistDevice::class, 'id_user_hair_stylist');
    }

    public function attendance_logs()
    {
        return $this->hasMany(HairstylistAttendanceLog::class, 'id_hairstylist_attendance', 'id_hairstylist_attendance');
    }
}
