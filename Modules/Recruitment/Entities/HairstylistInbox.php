<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserInbox
 * 
 * @property int $id_user_inboxes
 * @property int $id_campaign
 * @property int $id_user
 * @property string $inboxes_subject
 * @property string $inboxes_content
 * @property \Carbon\Carbon $inboxes_send_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class HairstylistInbox extends Model
{
	protected $primaryKey = 'id_hairstylist_inboxes';

	protected $casts = [
		'id_campaign' => 'int',
		'id_user_hair_stylist' => 'int'
	];

	protected $dates = [
		'inboxes_send_at'
	];

	protected $fillable = [
		'id_campaign',
		'id_user_hair_stylist',
		'inboxes_subject',
		'inboxes_clickto',
		'inboxes_link',
		'inboxes_id_reference',
		'inboxes_category',
		'inboxes_content',
		'inboxes_send_at'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}

	public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}
}
