<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InboxGlobalRead
 * 
 * @property int $id_inbox_global_read
 * @property int $id_inbox_global
 * @property int $id_user
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\InboxGlobal $inbox_global
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class InboxGlobalRead extends Model
{
	protected $casts = [
		'id_inbox_global_read' => 'int',
		'id_inbox_global' => 'int',
		'id_user' => 'int'
	];

	protected $fillable = [
		'id_inbox_global_read',
		'id_inbox_global',
		'id_user'
	];

	public function inbox_global()
	{
		return $this->belongsTo(\App\Http\Models\InboxGlobal::class, 'id_inbox_global');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
