<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignWhatsappSent extends Model
{
	protected $primaryKey = 'id_campaign_whatsapp_sent';

	protected $casts = [
		'id_campaign' => 'int',
	];

	protected $dates = [
		'whatsapp_sent_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'whatsapp_sent_to',
		'whatsapp_sent_subject',
		'whatsapp_sent_send_at',
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}

	public function campaign_whatsapp_sent_content()
	{
		return $this->hasMany(\App\Http\Models\CampaignWhatsappSentContent::class, 'id_campaign_whatsapp_sent');
	}
}
