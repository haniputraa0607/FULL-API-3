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
        'jumlah',
        'discount'
    ];

    protected $casts = [
        'id_bundling' => 'integer',
        'id_product' => 'integer',
        'id_brand' => 'integer',
        'jumlah' => 'integer',
        'discount' => 'decimal:2'
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
