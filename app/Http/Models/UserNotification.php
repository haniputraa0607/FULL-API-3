<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
	protected $table = 'user_notifications';

	protected $primaryKey = 'id_user_notification';
	
	protected $fillable   = [
		'id_user',
		'inbox',
		'voucher',
		'history'
	];
}
