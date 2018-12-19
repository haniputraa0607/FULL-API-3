<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignWhatsappQueue extends Model
{
	protected $primaryKey = 'id_campaign_whatsapp_queue';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $dates = [
		'whatsapp_queue_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'whatsapp_queue_to',
		'whatsapp_queue_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
