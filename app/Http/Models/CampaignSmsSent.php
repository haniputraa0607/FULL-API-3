<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignSmsSent
 * 
 * @property int $id_campaign_sms_sent
 * @property int $id_campaign
 * @property string $sms_sent_to
 * @property string $sms_sent_content
 * @property \Carbon\Carbon $sms_sent_send_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignSmsSent extends Model
{
	protected $primaryKey = 'id_campaign_sms_sent';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $dates = [
		'sms_sent_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'sms_sent_to',
		'sms_sent_content',
		'sms_sent_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
