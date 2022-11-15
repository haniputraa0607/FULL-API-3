<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 25 Nov 2020 09:16:32 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsDiscountBillProduct
 *
 * @property int $id_deals_discount_bill_product
 * @property int $id_deals
 * @property int $id_product
 * @property int $id_product_category
 * @property int $id_brand
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Modules\Deals\Entities\Deal $deal
 * @property \Modules\Deals\Entities\ProductCategory $product_category
 * @property \Modules\Deals\Entities\Product $product
 *
 * @package Modules\Deals\Entities
 */
class DealsDiscountBillProduct extends Eloquent
{
    protected $primaryKey = 'id_deals_discount_bill_product';

    protected $casts = [
        'id_deals' => 'int',
        'id_product' => 'int',
        'id_product_category' => 'int',
        'id_brand' => 'int'
    ];

    protected $fillable = [
        'id_deals',
        'id_product',
        'id_product_category',
        'id_brand',
        'id_product_variant_group'
    ];

    public function deal()
    {
        return $this->belongsTo(\Modules\Deals\Entities\Deal::class, 'id_deals');
    }

    public function product_category()
    {
        return $this->belongsTo(\App\Http\Models\ProductCategory::class, 'id_product_category');
    }

    public function product()
    {
        return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
    }

    public function brand()
    {
        return $this->belongsTo(\Modules\Brand\Entities\Brand::class, 'id_brand');
    }

    public function product_variant_pivot()
    {
        return $this->hasMany(\Modules\ProductVariant\Entities\ProductVariantPivot::class, 'id_product_variant_group', 'id_product_variant_group');
    }
}
