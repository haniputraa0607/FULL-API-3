<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignEmailSent
 * 
 * @property int $id_campaign_email_sent
 * @property int $id_campaign
 * @property string $email_sent_to
 * @property string $email_sent_subject
 * @property string $email_sent_message
 * @property \Carbon\Carbon $email_sent_send_at
 * @property int $email_sent_is_read
 * @property int $email_sent_is_clicked
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignEmailSent extends Model
{
	protected $primaryKey = 'id_campaign_email_sent';

	protected $casts = [
		'id_campaign' => 'int',
		'email_sent_is_read' => 'int',
		'email_sent_is_clicked' => 'int'
	];

	protected $dates = [
		'email_sent_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'email_sent_to',
		'email_sent_subject',
		'email_sent_message',
		'email_sent_send_at',
		'email_sent_is_read',
		'email_sent_is_clicked'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}
}
