<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class UserSocialLogin extends Model
{
    protected $primaryKey = 'id_user_social_login';

    protected $fillable = [
        'id_user',
        'provider',
        'provider_user_id'
    ];
}
