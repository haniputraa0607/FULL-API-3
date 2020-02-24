<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudDetectionLogReferralUsers extends Model
{
	protected $primaryKey = 'id_fraud_detection_log_referral_users';
	protected $table = 'fraud_detection_log_referral_users';

	protected $fillable = [
		'id_user',
        'id_promo_campaign_referral_transaction',
		'referral_code',
		'referral_code_use_date',
		'status'
	];


}
