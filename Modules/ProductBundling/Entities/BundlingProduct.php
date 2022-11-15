<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Product;
use Illuminate\Database\Eloquent\Model;

class BundlingProduct extends Model
{
    protected $table = 'bundling_product';

    protected $fillable = [
        'id_bundling',
        'id_product',
        'id_brand',
        'id_product_variant_group',
        'bundling_product_qty',
        'bundling_product_discount_type',
        'bundling_product_discount',
        'bundling_product_maximum_discount',
        'charged_central',
        'charged_outlet'
    ];

    public function products()
    {
        return $this->hasOne(Product::class, 'id_product', 'id_product');
    }

    public function bundlings()
    {
        return $this->hasOne(Bundling::class, 'id_bundling', 'id_bundling');
    }
}
