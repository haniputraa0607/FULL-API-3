<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductGlobalPrice extends Model
{
    protected $table = 'product_global_price';
    public $primaryKey = 'id_product_global_price';
    protected $fillable = [
        'id_product',
        'product_global_price',
        'global_price_before_discount',
        'global_price_discount_percent',
        'created_at',
        'updated_at'
    ];

    public function product()
    {
        return $this->belongsTo(App\Http\Models\Product::class, 'id_product');
    }
}
