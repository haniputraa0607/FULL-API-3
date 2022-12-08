<?php

namespace Modules\ProductVariant\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class ProductVariantGroupWholesaler extends Model
{
    protected $table = 'product_variant_group_wholesalers';
    protected $primaryKey = 'id_product_variant_group_wholesaler';

    protected $fillable = [
        'id_product_variant_group',
        'variant_wholesaler_minimum',
        'variant_wholesaler_unit_price',
        'variant_wholesaler_unit_price_discount_percent',
        'variant_wholesaler_unit_price_before_discount'
    ];
}
