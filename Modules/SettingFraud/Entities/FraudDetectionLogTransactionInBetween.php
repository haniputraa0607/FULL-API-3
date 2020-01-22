<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogTransactionInBetween extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_transaction_in_between';
	protected $table = 'fraud_detection_log_transaction_in_between';

	protected $fillable = [
		'id_user',
		'id_transaction',
        'status',
		'fraud_setting_parameter_detail',
        'fraud_setting_parameter_detail_time',
        'fraud_setting_parameter_detail_time_period',
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

    function transactions() {
        return $this->hasMany(FraudBetweenTransaction::class, 'id_fraud_detection_log_transaction_in_between', 'id_fraud_detection_log_transaction_in_between')
            ->join('transactions', 'transactions.id_transaction', 'fraud_between_transaction.id_transaction');
    }
}
