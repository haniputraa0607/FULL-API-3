<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignSmsQueue
 * 
 * @property int $id_campaign_sms_queue
 * @property int $id_campaign
 * @property string $sms_queue_to
 * @property string $sms_queue_content
 * @property \Carbon\Carbon $sms_queue_send_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignSmsQueue extends Model
{
	protected $primaryKey = 'id_campaign_sms_queue';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $dates = [
		'sms_queue_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'sms_queue_to',
		'sms_queue_content',
		'sms_queue_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
