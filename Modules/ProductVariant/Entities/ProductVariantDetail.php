<?php

namespace Modules\ProductVariant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductVariantDetail extends Model
{
    protected $fillable = [
    	'id_outlet',
    	'id_product_variant',
    	'product_variant_stock_status'
    ];
}
