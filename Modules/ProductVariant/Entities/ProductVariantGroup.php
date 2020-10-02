<?php

namespace Modules\ProductVariant\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class ProductVariantGroup extends Model
{
    protected $primaryKey = 'id_product_variant_group';

    protected $fillable = [
        'id_product',
        'product_variant_group_code',
        'product_variant_group_name',
        'product_variant_group_visibility'
    ];

    public function product_variant_pivot()
    {
        return $this->hasMany(ProductVariantPivot::class, 'id_product_variant_group', 'id_product_variant_group')
            ->join('product_variant', 'product_variant.id_product_variant', 'product_variant_pivot.id_product_variant');
    }

    public function id_product_variants()
    {
        return $this->hasMany(ProductVariantPivot::class, 'id_product_variant_group', 'id_product_variant_group')->select('id_product_variant_group','id_product_variant');
    }
}
