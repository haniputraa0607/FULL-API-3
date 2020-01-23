<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Campaign
 * 
 * @property int $id_campaign
 * @property string $campaign_title
 * @property int $id_user
 * @property \Carbon\Carbon $campaign_send_at
 * @property string $campaign_generate_receipient
 * @property string $campaign_rule
 * @property string $campaign_media_email
 * @property string $campaign_media_sms
 * @property string $campaign_media_push
 * @property string $campaign_media_inbox
 * @property int $campaign_email_count_all
 * @property int $campaign_email_count_queue
 * @property int $campaign_email_count_sent
 * @property int $campaign_sms_count_all
 * @property int $campaign_sms_count_queue
 * @property int $campaign_sms_count_sent
 * @property int $campaign_push_count_all
 * @property int $campaign_push_count_queue
 * @property int $campaign_push_count_sent
 * @property int $campaign_inbox_count
 * @property string $campaign_email_receipient
 * @property string $campaign_sms_receipient
 * @property string $campaign_push_receipient
 * @property string $campaign_inbox_receipient
 * @property string $campaign_email_subject
 * @property string $campaign_email_content
 * @property string $campaign_sms_content
 * @property string $campaign_push_subject
 * @property string $campaign_push_content
 * @property string $campaign_push_image
 * @property string $campaign_push_clickto
 * @property string $campaign_push_link
 * @property string $campaign_push_id_reference
 * @property string $campaign_inbox_subject
 * @property string $campaign_inbox_content
 * @property string $campaign_is_sent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection $campaign_email_queues
 * @property \Illuminate\Database\Eloquent\Collection $campaign_email_sents
 * @property \Illuminate\Database\Eloquent\Collection $campaign_push_queues
 * @property \Illuminate\Database\Eloquent\Collection $campaign_push_sents
 * @property \Illuminate\Database\Eloquent\Collection $campaign_rules
 * @property \Illuminate\Database\Eloquent\Collection $campaign_sms_queues
 * @property \Illuminate\Database\Eloquent\Collection $campaign_sms_sents
 * @property \Illuminate\Database\Eloquent\Collection $inbox_globals
 * @property \Illuminate\Database\Eloquent\Collection $user_inboxes
 *
 * @package App\Models
 */
class Campaign extends Model
{
	protected $primaryKey = 'id_campaign';

	protected $casts = [
		'id_user' => 'int',
		'campaign_email_count_all' => 'int',
		'campaign_email_count_queue' => 'int',
		'campaign_email_count_sent' => 'int',
		'campaign_sms_count_all' => 'int',
		'campaign_sms_count_queue' => 'int',
		'campaign_sms_count_sent' => 'int',
		'campaign_push_count_all' => 'int',
		'campaign_push_count_queue' => 'int',
		'campaign_push_count_sent' => 'int',
		'campaign_inbox_count' => 'int',
		'campaign_whatsapp_count' => 'int'
	];

	protected $dates = [
		'campaign_send_at'
	];

	protected $fillable = [
		'campaign_title',
		'id_user',
		'campaign_send_at',
		'campaign_generate_receipient',
		'campaign_rule',
		'campaign_media_email',
		'campaign_media_sms',
		'campaign_media_push',
		'campaign_media_inbox',
		'campaign_media_whatsapp',
		'campaign_email_count_all',
		'campaign_email_count_queue',
		'campaign_email_count_sent',
		'campaign_sms_count_all',
		'campaign_sms_count_queue',
		'campaign_sms_count_sent',
		'campaign_push_count_all',
		'campaign_push_count_queue',
		'campaign_push_count_sent',
		'campaign_inbox_count',
		'campaign_whatsapp_count_all',
		'campaign_whatsapp_count_queue',
		'campaign_whatsapp_count_sent',
		'campaign_email_receipient',
		'campaign_sms_receipient',
		'campaign_push_receipient',
		'campaign_inbox_receipient',
		'campaign_whatsapp_receipient',
		'campaign_email_subject',
		'campaign_email_content',
		'campaign_sms_content',
		'campaign_push_subject',
		'campaign_push_content',
		'campaign_push_image',
		'campaign_push_clickto',
		'campaign_push_link',
		'campaign_push_id_reference',
		'campaign_inbox_subject',
		'campaign_inbox_content',
		'campaign_inbox_clickto',
		'campaign_inbox_link',
		'campaign_inbox_id_reference',
		'campaign_whatsapp_content',
		'campaign_is_sent',
		'campaign_description'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function campaign_email_sents()
	{
		return $this->hasMany(\App\Http\Models\CampaignEmailSent::class, 'id_campaign');
	}

	public function campaign_push_sents()
	{
		return $this->hasMany(\App\Http\Models\CampaignPushSent::class, 'id_campaign');
	}

	public function campaign_rule_parents()
	{
		return $this->hasMany(\App\Http\Models\CampaignRuleParent::class, 'id_campaign')
					->select('id_campaign_rule_parent', 'id_campaign', 'campaign_rule as rule', 'campaign_rule_next as rule_next');
	}

	public function campaign_sms_sents()
	{
		return $this->hasMany(\App\Http\Models\CampaignSmsSent::class, 'id_campaign');
	}

	public function inbox_globals()
	{
		return $this->hasMany(\App\Http\Models\InboxGlobal::class, 'id_campaign');
	}

	public function user_inboxes()
	{
		return $this->hasMany(\App\Http\Models\UserInbox::class, 'id_campaign');
	}

	public function whatsapp_content()
	{
		return $this->hasMany(\App\Http\Models\WhatsappContent::class, 'id_reference', 'id_campaign')
					->where('source', 'campaign');
	}
}
