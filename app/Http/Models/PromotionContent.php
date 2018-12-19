<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionContent extends Model
{
	protected $primaryKey = 'id_promotion_content';

	protected $casts = [
		'id_promotion' => 'int',
		'id_deal' => 'int',
	];

	protected $fillable = [
		'id_promotion',
		'id_deals',
		'promotion_series_days',
		'promotion_series_no',
		'voucher_value',
		'voucher_given',
		'promotion_channel_email',
		'promotion_channel_sms',
		'promotion_channel_push',
		'promotion_channel_inbox',
		'promotion_channel_whatsapp',
		'promotion_channel_forward',
		'promotion_email_subject',
		'promotion_email_content',
		'promotion_sms_content',
		'promotion_push_subject',
		'promotion_push_content',
		'promotion_push_image',
		'promotion_push_clickto',
		'promotion_push_link',
		'promotion_push_id_reference',
		'promotion_inbox_subject',
		'promotion_inbox_content',
		'promotion_inbox_clickto',
		'promotion_inbox_link',
		'promotion_inbox_id_reference',
		'promotion_forward_email',
		'promotion_forward_email_subject',
		'promotion_forward_email_content',
		'promotion_forward_email_content',
		'promotion_count_email_sent',
		'promotion_count_email_read',
		'promotion_count_email_link_clicked',
		'promotion_count_sms_sent',
		'promotion_count_push',
		'promotion_count_inbox',
		'promotion_count_whatsapp',
		'promotion_count_whatsapp_link_clicked',
		'promotion_count_voucher_give',
		'promotion_count_voucher_used',
		'promotion_sum_transaction',
		'created_at',
		'updated_at',
	];

	public function promotion()
	{
		return $this->belongsTo(\App\Http\Models\Promotion::class, 'id_promotion');
	}
	
	public function deals()
	{
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}

	public function promotionContentShortenLink()
	{
		return $this->belongsTo(\App\Http\Models\PromotionContentShortenLink::class, 'id_deals');
	}

	public function whatsapp_content()
	{
		return $this->hasMany(\App\Http\Models\WhatsappContent::class, 'id_reference', 'id_promotion_content')
					->where('source', 'promotion');
	}
}
