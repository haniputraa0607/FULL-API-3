<?php

namespace Modules\Franchise\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class UserFranchise extends Authenticatable
{
    use Notifiable;
    use HasMultiAuthApiTokens;

    protected $table = 'user_franchises';
    protected $primaryKey = 'id_user_franchise';

    public function findForPassport($email)
    {
        return $this->where('username', $email)->first();
    }
    protected $appends = ['password_default_decrypt'];
    protected $fillable = [
        'id_user_franchise_seed',
        'username',
        'phone',
        'name',
        'level',
        'email',
        'user_franchise_status',
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
