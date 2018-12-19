<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignPushQueue
 * 
 * @property int $id_campaign_push_queue
 * @property int $id_campaign
 * @property string $push_queue_to
 * @property string $push_queue_subject
 * @property string $push_queue_content
 * @property \Carbon\Carbon $push_queue_send_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignPushQueue extends Model
{
	protected $primaryKey = 'id_campaign_push_queue';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $dates = [
		'push_queue_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'push_queue_to',
		'push_queue_subject',
		'push_queue_content',
		'push_queue_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
