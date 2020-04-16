<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class UserFranchise extends Authenticatable
{
    use Notifiable, HasMultiAuthApiTokens;

    protected $table = 'user_franchises';
    protected $primaryKey = 'id_user_franchise';

    public function findForPassport($username) {
        if(substr($username, 0, 2) == '62'){
            $username = substr($username,2);
        }elseif(substr($username, 0, 3) == '+62'){
            $username = substr($username,3);
        }

        if(substr($username, 0, 1) != '0'){
            $username = '0'.$username;
        }

        return $this->where('phone', $username)->first();
    }

	protected $fillable = [
		'id_user_franchise_seed',
	    'phone',
		'email',
        'password',
        'user_franchise_type'
	];
}
