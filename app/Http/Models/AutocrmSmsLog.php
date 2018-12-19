<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AutocrmSmsLog
 * 
 * @property int $id_autocrm_sms_log
 * @property int $id_user
 * @property string $sms_log_to
 * @property string $sms_log_content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class AutocrmSmsLog extends Model
{
	protected $primaryKey = 'id_autocrm_sms_log';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $fillable = [
		'id_user',
		'sms_log_to',
		'sms_log_content'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
