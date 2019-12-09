<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UsersDeviceLogin extends Model
{
    protected $primaryKey = 'id_user_last_login';
    protected $table = 'users_device_login';

    protected $fillable = [
        'id_user',
        'device_id',
        'last_login',
        'created_at',
        'updated_at'
    ];

    function user() {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

}
