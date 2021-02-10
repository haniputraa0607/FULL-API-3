<?php

namespace Modules\UserFranchise\Entities;

use App\Lib\MyHelper;
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

    public function findForPassport($email) {
        return $this->where('email', $email)->first();
    }
    protected $appends = ['password_default_decrypt'];
	protected $fillable = [
		'id_user_franchise_seed',
	    'phone',
        'name',
        'level',
		'email',
        'password',
        'password_default_plain_text',
        'user_franchise_type',
        'first_update_password'
	];

    public function getPasswordDefaultDecryptAttribute()
    {
        return MyHelper::decrypt2019($this->password_default_plain_text);
    }
}
