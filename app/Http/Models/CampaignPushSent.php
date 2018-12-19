<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignPushSent
 * 
 * @property int $id_campaign_push_sent
 * @property int $id_campaign
 * @property string $push_sent_to
 * @property string $push_sent_subject
 * @property string $push_sent_content
 * @property \Carbon\Carbon $push_sent_send_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignPushSent extends Model
{
	protected $primaryKey = 'id_campaign_push_sent';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $dates = [
		'push_sent_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'push_sent_to',
		'push_sent_subject',
		'push_sent_content',
		'push_sent_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
