<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifierStockStatusUpdate extends Model
{
    protected $fillable = [
    	'id_product_modifier',
    	'id_outlet',
    	'id_user',
        'user_name',
        'user_email',
    	'id_outlet_app_otp',
    	'user_type',
    	'date_time',
    	'new_status'
    ];

    public function getUserAttribute($value)
    {
        [$table,$id_user,$name] = explode(',',$value);
        if($table == 'users'){
            return \App\Http\Models\User::select('name')->where('id',$id_user)->pluck('name')->first();
        }elseif($table == 'user_outlets'){
            return \App\Http\Models\UserOutlet::select('name')->where('id_user_outlet',$id_user)->pluck('name')->first();
        }else{
            return $name;
        }
    }
    public function getNewStatusAttribute($value)
    {
        if($value == 'Available'){
            return 'Stock Tersedia';
        }else{
            return 'Stock Habis';
        }
    }
    public function getOldStatusAttribute($new_value)
    {
        if($new_value == 'Available'){
            return 'Stock Habis';
        }else{
            return 'Stock Tersedia';
        }
    }
    public function getToAvailableAttribute($new_value)
    {
        return $new_value == 'Available'?1:0;
    }
}
