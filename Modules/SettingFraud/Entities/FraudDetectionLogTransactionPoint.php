<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogTransactionPoint extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_transaction_point';
	protected $table = 'fraud_detection_log_transaction_point';

	protected $fillable = [
		'id_user',
		'current_balance',
		'at_outlet',
		'most_outlet',
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

    function mostOutlet() {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'most_outlet', 'id_outlet');
    }

    function atOutlet() {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'at_outlet', 'id_outlet');
    }
}
