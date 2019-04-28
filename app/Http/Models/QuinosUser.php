<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class QuinosUser extends Authenticatable
{
	use Notifiable, HasMultiAuthApiTokens;

	public function findForPassport($username) {
        return $this->where('username', $username)->first();
	}
	
	public function getAuthPassword() {
		return $this->password;
	}

	protected $primaryKey = 'id_quinos_user';

	protected $hidden = ['password'];

	protected $fillable = [
		'username',
		'password',
	];

}
