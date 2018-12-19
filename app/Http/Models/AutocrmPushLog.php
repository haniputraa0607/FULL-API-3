<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:14 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AutocrmPushLog
 * 
 * @property int $id_autocrm_push_log
 * @property int $id_user
 * @property string $push_log_to
 * @property string $push_log_subject
 * @property string $push_log_content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class AutocrmPushLog extends Model
{
	protected $primaryKey = 'id_autocrm_push_log';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $fillable = [
		'id_user',
		'push_log_to',
		'push_log_subject',
		'push_log_content'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
