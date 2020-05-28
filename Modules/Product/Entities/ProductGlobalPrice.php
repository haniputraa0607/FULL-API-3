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
        'created_at',
        'updated_at'
    ];
}
