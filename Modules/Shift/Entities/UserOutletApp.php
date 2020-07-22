<?php

namespace Modules\Shift\Entities;

use Illuminate\Database\Eloquent\Model;

class UserOutletApp extends Model
{

    protected $table = "user_outletapps";

    protected $primaryKey = "id_user_outletapp";

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
