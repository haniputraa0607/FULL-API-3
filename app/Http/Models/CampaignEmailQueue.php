<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignEmailQueue
 * 
 * @property int $id_campaign_email_queue
 * @property int $id_campaign
 * @property string $email_queue_to
 * @property string $email_queue_subject
 * @property string $email_queue_content
 * @property \Carbon\Carbon $email_queue_send_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignEmailQueue extends Model
{
	protected $primaryKey = 'id_campaign_email_queue';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $dates = [
		'email_queue_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'email_queue_to',
		'email_queue_subject',
		'email_queue_content',
		'email_queue_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
