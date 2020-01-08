<?php

namespace Modules\UserFeedback\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFeedbackLog extends Model
{
	protected $primaryKey = 'id_user_feedback_log';
    protected $fillable = ['id_user','last_popup','refuse_count'];
}
