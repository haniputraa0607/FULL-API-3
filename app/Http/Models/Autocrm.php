<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Autocrm extends Model
{
	protected $primaryKey = 'id_autocrm';

	protected $fillable = [
		'autocrm_type',
		'autocrm_trigger',
		'autocrm_cron_rule',
		'autocrm_cron_reference',
		'autocrm_title',
		'autocrm_email_toogle',
		'autocrm_sms_toogle',
		'autocrm_push_toogle',
		'autocrm_inbox_toogle',
		'autocrm_whatsapp_toogle',
		'autocrm_forward_toogle',
		'autocrm_email_subject',
		'autocrm_email_content',
		'autocrm_sms_content',
		'autocrm_push_subject',
		'autocrm_push_content',
		'autocrm_push_image',
		'autocrm_push_clickto',
		'autocrm_push_link',
		'autocrm_push_id_reference',
		'autocrm_inbox_subject',
		'autocrm_inbox_content',
		'autocrm_inbox_clickto',
		'autocrm_inbox_link',
		'autocrm_inbox_id_reference',
		'autocrm_forward_email',
		'autocrm_forward_email_subject',
		'autocrm_forward_email_content',
		'attachment_mail',
		'attachment_forward'
	];

	public function autocrm_rule_parents()
	{
		return $this->hasMany(\App\Http\Models\AutocrmRuleParent::class, 'id_autocrm')
					->select('id_autocrm_rule_parent', 'id_autocrm', 'autocrm_rule as rule', 'autocrm_rule_next as rule_next');
	}

	public function whatsapp_content()
	{
		return $this->hasMany(\App\Http\Models\WhatsappContent::class, 'id_reference', 'id_autocrm')
					->where('source', 'autocrm');
	}
}
