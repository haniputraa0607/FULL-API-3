<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class AutocrmWhatsappLogContent extends Model
{
	protected $primaryKey = 'id_autocrm_whatsapp_log_content';

	protected $fillable = [
		'id_autocrm_whatsapp_log',
		'content_type',
		'content'
	];

	public function autocrm_whatsapp_log()
	{
		return $this->belongsTo(\App\Http\Models\AutocrmWhatsappContent::class, 'id_autocrm_whatsapp_log', 'id_autocrm_whatsapp_log');
	}

}
