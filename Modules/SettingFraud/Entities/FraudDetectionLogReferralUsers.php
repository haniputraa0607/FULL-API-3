<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogReferralUsers extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_referral_users';
	protected $table = 'fraud_detection_log_referral_users';

	protected $fillable = [
		'id_user',
        'id_transaction',
		'referral_code',
		'referral_code_use_date',
        'execution_status',
        'fraud_setting_forward_admin_status',
        'fraud_setting_auto_suspend_status'
	];


}
