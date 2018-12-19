<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class AutocrmWhatsappLog extends Model
{
	protected $primaryKey = 'id_autocrm_whatsapp_log';

	protected $casts = [
		'id_autocrm_whatsapp_log' => 'int',
		'id_user' => 'int',
	];

	protected $fillable = [
		'id_user',
		'whatsapp_log_to',
	];

	public function autocrm()
	{
		return $this->belongsTo(\App\Http\Models\Autocrm::class, 'id_autocrm');
	}

	public function autocrm_whatsapp_log_content()
	{
		return $this->hasMany(\App\Http\Models\AutocrmWhatsappLogContent::class, 'id_autocrm_whatsapp_log');
	}
}
