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
}
