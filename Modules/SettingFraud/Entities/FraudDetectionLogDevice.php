<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogDevice extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_device';
	protected $table = 'fraud_detection_log_device';

	protected $fillable = [
		'id_user',
		'device_id',
        'device_type',
		'last_login',
		'status',
		'fraud_setting_parameter_detail',
		'fraud_setting_forward_admin_status',
        'fraud_setting_auto_suspend_status',
        'fraud_setting_auto_suspend_value',
        'fraud_setting_auto_suspend_time_period',
        'created_at',
        'updated_at'
	];

	function user() {
		return $this->belongsTo(User::class, 'id_user', 'id');
	}

	function usersFraud(){
        return $this->hasMany(UsersDeviceLogin::class, 'device_id', 'device_id')
            ->join('users', 'users_device_login.id_user', '=', 'users.id')
            ->whereRaw('users_device_login.id_user in (Select id_user from fraud_detection_log_device where users_device_login.device_id = fraud_detection_log_device.device_id)')
            ->orderBy('users_device_login.last_login','desc');
    }

    function usersNoFraud(){
        return $this->hasMany(UsersDeviceLogin::class, 'device_id', 'device_id')
            ->join('users', 'users_device_login.id_user', '=', 'users.id')
            ->whereRaw('users_device_login.id_user not in (Select id_user from fraud_detection_log_device where users_device_login.device_id = fraud_detection_log_device.device_id)')
            ->orderBy('users_device_login.last_login','desc');
    }

    function allUsersdevice(){
        return $this->hasMany(UsersDeviceLogin::class, 'device_id', 'device_id')
            ->join('users', 'users_device_login.id_user', '=', 'users.id')
            ->orderBy('users_device_login.last_login','desc');
    }
}
