<?php

namespace Modules\Brand\Entities;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands';

    protected $primaryKey = 'id_brand';

    protected $fillable   = [
        'name_brand',
        'brand_active',
        'code_brand',
        'logo_brand',
        'image_brand',
        'order_brand'
    ];

    public function getLogoBrandAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return env('S3_URL_API') . $value;
    }

    public function getImageBrandAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return env('S3_URL_API') . $value;
    }

    public function products(){
        return $this->belongsToMany(\App\Http\Models\Product::class, 'brand_product','id_brand','id_product');
    }

    public function outlets(){
        return $this->belongsToMany(\App\Http\Models\Outlet::class, 'brand_outlet','id_brand','id_outlet');
    }
}
