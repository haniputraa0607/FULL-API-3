<?php

namespace Modules\Brand\Entities;

use Illuminate\Database\Eloquent\Model;

class BrandProduct extends Model
{
    protected $table = 'brand_product';

    protected $primaryKey = 'id_brand_product';

    protected $fillable   = [
        'id_brand',
        'id_product',
        'id_product_category'
    ];

    public function products(){
        return $this->belongsTo(\App\Http\Models\Product::class, 'id_product', 'id_product');
    }
}
