<?php

namespace Modules\Brand\Entities;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands';

    protected $primaryKey = 'id_brand';

    protected $fillable   = [
        'name_brand',
        'code_brand',
        'logo_brand',
        'image_brand',
        'order_brand'
    ];

    public function getLogoBrandAttribute($value)
    {
        return env('API_URL') . $value;
    }

    public function getImageBrandAttribute($value)
    {
        return env('API_URL') . $value;
    }
}
