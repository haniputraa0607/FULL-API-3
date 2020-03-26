<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductStockStatusUpdate extends Model
{
    protected $fillable = [
    	'id_product',
    	'id_outlet',
    	'id_user',
    	'id_outlet_app_otp',
    	'user_type',
    	'date_time',
    	'new_status'
    ];
    public function getUserAttribute($value)
    {
    	[$table,$id_user] = explode(',',$value);
    	if($table == 'users'){
    		return \App\Http\Models\User::select('name')->where('id',$id_user)->pluck('name')->first();
    	}else{
    		return \App\Http\Models\UserOutlet::select('name')->where('id_user_outlet',$id_user)->pluck('name')->first();
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
    public function getFromSoldOutAttribute($new_value)
    {
        return $new_value == 'Available'?1:0;
    }
}
