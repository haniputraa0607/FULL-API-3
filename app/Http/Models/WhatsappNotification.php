<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNotification extends Model
{
	protected $primaryKey = 'id_whatsapp_notification';

	protected $fillable = [
		'notification_days',
		'notification_times',
		'message_contain_text',
		'notification_channel_email',
		'notification_channel_sms',
		'notification_channel_whatsapp',
		'notification_email_recipient',
		'notification_sms_recipient',
		'notification_whatsapp_recipient',
		'notification_email_subject',
		'notification_email_content',
		'notification_sms_content',
		'notification_whatsapp_content'
	];

}
