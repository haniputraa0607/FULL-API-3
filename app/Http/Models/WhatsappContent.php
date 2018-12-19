<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappContent extends Model
{
	protected $primaryKey = 'id_whatsapp_content';
	protected $table = 'whatsapp_contents';

	protected $fillable = [
		'source',
		'id_reference',
		'content_type',
		'content'
	];

	public function autocrm()
	{
		return $this->belongsTo(\App\Http\Models\Autocrm::class, 'id_autocrm', 'id_reference')
					->where('source', 'autocrm');
	}

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign', 'id_reference')
					->where('source', 'campaign');
	}

	public function promotion()
	{
		return $this->belongsTo(\App\Http\Models\Promotion::class, 'id_promotion', 'id_reference')
					->where('source', 'promotion');
	}

}
