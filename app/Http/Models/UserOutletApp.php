<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;

class UserOutletApp extends Authenticatable
{
	use Notifiable, HasMultiAuthApiTokens;

    public function findForPassport($username) {
        return $this->where('username', $username)->first();
	}

	public function getAuthPassword() {
		return $this->password;
     }
     
    protected $table = "user_outletapps";

    protected $primaryKey = "id_user_outletapp";

    protected $hidden = ['password'];

    protected $fillable = [
        'id_outlet',
        'id_brand',
        'username',
        'password',
        'level'
    ];

    public function outlet(){
        return $this->belongsTo('App\Http\Models\Outlet', 'id_outlet');
    }

    public function brand(){
        return $this->belongsTo(Modules\Brand\Entities\Brand::class, 'id_brand');
    }
}
