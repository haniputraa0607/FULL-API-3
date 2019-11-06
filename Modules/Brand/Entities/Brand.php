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
        'brand_visibility',
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

    public function brand_product(){
        return $this->hasMany(BrandProduct::class, 'id_brand', 'id_brand');
    }

    public function brand_outlet(){
        return $this->hasMany(BrandOutlet::class, 'id_brand', 'id_brand');
    }
}
