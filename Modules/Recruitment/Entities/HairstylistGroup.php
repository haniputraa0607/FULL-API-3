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
class HairstylistGroup extends Model
{
	protected $table = 'hairstylist_groups';
	protected $primaryKey = 'id_hairstylist_group';


	protected $fillable = [
		'hair_stylist_group_name',
		'hair_stylist_group_code',
		'hair_stylist_group_description',
                'created_at',
                'updated_at'
	];
        public function commission(){
            return $this->hasMany(HairstylistGroupCommission::class, 'id_hairstylist_group');
        }
}
