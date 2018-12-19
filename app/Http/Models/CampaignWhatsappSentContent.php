<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignWhatsappSentContent extends Model
{
	protected $primaryKey = 'id_campaign_whatsapp_sent_content';

	protected $fillable = [
		'id_campaign_whatsapp_sent',
		'content_type',
		'content'
	];

	public function campaign_whatsapp_sent()
	{
		return $this->belongsTo(\App\Http\Models\CampaignWhatsappContent::class, 'id_campaign_whatsapp_sent', 'id_campaign_whatsapp_sent');
	}

}
