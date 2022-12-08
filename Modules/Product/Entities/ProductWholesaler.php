<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductWholesaler extends Model
{
    protected $table = 'product_wholesalers';
    public $primaryKey = 'id_product_wholesaler';
    protected $fillable = [
        'id_product',
        'product_wholesaler_minimum',
        'product_wholesaler_unit_price',
        'wholesaler_unit_price_before_discount',
        'wholesaler_unit_price_discount_percent'
    ];
}
