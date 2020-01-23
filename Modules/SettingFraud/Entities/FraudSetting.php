<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudSetting extends Model
{
	protected $primaryKey = 'id_fraud_setting';
	protected $table = 'fraud_settings';

	protected $fillable = [
		'parameter',
		'parameter_detail',
		'email_toogle',
		'sms_toogle',
		'whatsapp_toogle',
		'email_recipient',
		'email_subject',
		'email_content',
		'sms_recipient',
		'sms_content',
		'whatsapp_recipient',
		'whatsapp_content'
	];

}
