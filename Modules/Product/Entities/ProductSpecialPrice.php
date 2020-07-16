<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductSpecialPrice extends Model
{
    protected $table = 'product_special_price';
    public $primaryKey = 'id_product_special_price';
    protected $fillable = [
        'id_product',
        'id_outlet',
        'product_special_price',
        'created_at',
        'updated_at'
    ];

    public function product(){
        return $this->belongsTo(App\Http\Models\Product::class, 'id_product');
    }

    public function outlet(){
        return $this->belongsTo(App\Http\Models\Outlet::class, 'id_outlet');
    }
}
