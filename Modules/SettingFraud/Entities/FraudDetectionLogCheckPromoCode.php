<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogCheckPromoCode extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_check_promo_code';
	protected $table = 'fraud_detection_log_check_promo_code';

	protected $fillable = [
		'id_user',
		'count',
        'status',
		'fraud_setting_parameter_detail',
        'fraud_hold_time',
        'fraud_parameter_detail_time',
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
