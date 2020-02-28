<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogReferral extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_referral';
	protected $table = 'fraud_detection_log_referral';

	protected $fillable = [
		'id_user',
		'id_transaction',
        'referral_code',
        'referral_code_use_date',
        'execution_status',
		'fraud_setting_parameter_detail',
        'fraud_setting_parameter_detail_time',
        'fraud_setting_auto_suspend_status',
        'fraud_setting_forward_admin_status',
        'created_at',
        'updated_at'
	];

	function user() {
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user', 'id');
	}
}
