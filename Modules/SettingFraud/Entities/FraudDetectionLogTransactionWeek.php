<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogTransactionWeek extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_transaction_week';
	protected $table = 'fraud_detection_log_transaction_week';

	protected $fillable = [
		'id_user',
        'fraud_detection_year',
        'fraud_detection_week',
		'count_transaction_week',
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

}
