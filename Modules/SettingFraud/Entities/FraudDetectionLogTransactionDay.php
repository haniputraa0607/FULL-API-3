<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogTransactionDay extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_transaction_day';
	protected $table = 'fraud_detection_log_transaction_day';

	protected $fillable = [
		'id_user',
		'fraud_detection_date',
		'count_transaction_day',
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
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user', 'id');
	}

}
