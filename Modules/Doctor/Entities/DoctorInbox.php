<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Doctor\Entities;

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
class DoctorInbox extends Model
{
    protected $table = 'doctor_inboxes';

    protected $primaryKey = 'id_doctor_inboxes';

    protected $dates = [
        'inboxes_send_at'
    ];

    protected $fillable = [
        'id_campaign',
        'id_doctor',
        'inboxes_subject',
        'inboxes_clickto',
        'inboxes_link',
        'inboxes_id_reference',
        'inboxes_content',
        'inboxes_send_at',
        'inboxes_promotion_status',
        'read',
        'id_brand'
    ];

    public function campaign()
    {
        return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }
}
